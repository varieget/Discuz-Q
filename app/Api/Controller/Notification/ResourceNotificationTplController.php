<?php

/**
 * Copyright (C) 2020 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Api\Controller\Notification;

use App\Api\Serializer\NotificationTplSerializer;
use App\Models\NotificationTpl;
use Discuz\Api\Controller\AbstractListController;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Contracts\Setting\SettingsRepository;
use EasyWeChat\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ResourceNotificationTplController extends AbstractListController
{
    use AssertPermissionTrait;

    /**
     * {@inheritdoc}
     */
    public $serializer = NotificationTplSerializer::class;

    protected $settings;

    /**
     * WechatChannel constructor.
     *
     * @param SettingsRepository $settings
     */
    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return mixed
     * @throws \Discuz\Auth\Exception\PermissionDeniedException
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $type_name = Arr::get($request->getQueryParams(), 'type_name');

        $tpl = NotificationTpl::query();

        $tpl->when(Arr::has($request->getQueryParams(), 'type'), function ($query) use ($request) {
            $query->where('type', (int) Arr::get($request->getQueryParams(), 'type'));
        });

        $typeNames = explode(',', $type_name);

        $query = $tpl->whereIn('type_name', $typeNames)->orderBy('type');

        $data = $query->get();

        /**
         * 检测是否存在小程序通知，查询小程序模板变量 key 值
         *
         * @URL 订阅消息参数值内容限制说明: https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/subscribe-message/subscribeMessage.send.html
         */
        $miniProgram = $data->where('type', NotificationTpl::MINI_PROGRAM_NOTICE);
        if ($miniProgram->isNotEmpty()) {
            $data->map(function ($item) {
                if ($item->type == NotificationTpl::MINI_PROGRAM_NOTICE) {
                    $keys = $this->getMiniProgramKeys($item->template_id);
                    $item->keys = $keys;
                }
            });
        }

        return $data;
    }

    private function getMiniProgramKeys($templateId)
    {
        $appID = $this->settings->get('miniprogram_app_id', 'wx_miniprogram');
        $secret = $this->settings->get('miniprogram_app_secret', 'wx_miniprogram');

        $app = Factory::miniProgram([
            'app_id' => $appID,
            'secret' => $secret,
        ]);

        $response = $app->subscribe_message->getTemplates();
        if (! isset($response['errcode']) || $response['errcode'] != 0 || count($response['data']) == 0) {
            return [];
        }

        $collect = collect($response['data']);
        $template = $collect->where('priTmplId', $templateId)->first();
        if (is_null($template)) {
            return [];
        }

        $content = $template['content'];
        $regex = '/{{(?<key>.*)\.DATA/';
        if (preg_match_all($regex, $content, $keys)) {
            return $keys['key'];
        }

        return [];
    }
}
