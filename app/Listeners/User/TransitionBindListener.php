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

namespace App\Listeners\User;


use App\Common\AuthUtils;
use App\Common\ResponseCode;
use App\Common\Utils;
use App\Events\Users\Logind;
use App\Events\Users\TransitionBind;
use App\Models\SessionToken;
use App\Models\UserWechat;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Psr\Http\Message\ServerRequestInterface;
use Discuz\Contracts\Setting\SettingsRepository;
use Discuz\Foundation\Application;

/**
 * 处理过渡期间微信绑定的问题
 * Class TransitionBindListener
 * @package App\Listeners\User
 */
class TransitionBindListener
{

    /**
     * @var SettingsRepository
     */
    public $settings;

    public $app;

    public $events;

    public $db;

    /**
     * @param SettingsRepository $settings
     * @param Application $app
     */
    public function __construct(SettingsRepository $settings, Application $app, Dispatcher $events, ConnectionInterface $db)
    {
        $this->settings = $settings;
        $this->app = $app;
        $this->events = $events;
        $this->db = $db;
    }

    /**
     * @param TransitionBind $event
     */
    public function handle($event)
    {
        $user = $event->user;
        $sessionToken = SessionToken::get($event->data['sessionToken']);
        if(empty($sessionToken) || ! $sessionToken) {
            // 长时间未操作，授权超时，重新授权
            \Discuz\Common\Utils::outPut(ResponseCode::AUTH_INFO_HAD_EXPIRED);
        }
        if(! $sessionToken->payload || empty($sessionToken->payload)) {
            // 授权信息未查询到，需要重新授权
            \Discuz\Common\Utils::outPut(ResponseCode::AUTH_INFO_HAD_EXPIRED);
        }
        $userWechatId = isset($sessionToken->payload['user_wechat_id'])? $sessionToken->payload['user_wechat_id'] : 0;
        //微信绑定用户操作
        $this->db->beginTransaction();
        try {
            $wechatUser = UserWechat::query()->where('id', $userWechatId)->lockForUpdate()->first();
            if(! $wechatUser) {
                $this->db->commit();
                // 授权信息未查询到，需要重新授权
                \Discuz\Common\Utils::outPut(ResponseCode::AUTH_INFO_HAD_EXPIRED);
            }
            //微信信息绑定用户信息
            $wechatUser->user_id = $user->id;
            $wechatUser->setRelation('user', $user);
            $wechatUser->save();

            //user 中绑定字段维护
//            $user->changeNickname($wechatUser->nickname);
            $user->bind_type = $user->bind_type + AuthUtils::WECHAT;
            $user->save();

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->commit();
            \Discuz\Common\Utils::outPut(ResponseCode::INTERNAL_ERROR);
        }

        return $user;
    }

}
