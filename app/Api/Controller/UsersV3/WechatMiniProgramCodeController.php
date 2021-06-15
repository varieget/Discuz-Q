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

namespace App\Api\Controller\UsersV3;

use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use App\Settings\SettingsRepository;
use Discuz\Auth\Exception\NotAuthenticatedException;
use Discuz\Base\DzqController;
use Discuz\Wechat\EasyWechatTrait;

/**
 * 微信小程序 - 小程序码
 *
 * @package App\Api\Controller\Wechat
 */
class WechatMiniProgramCodeController extends DzqController
{
    use EasyWechatTrait;

    protected $settings;

    /**
     * WechatMiniProgramCodeController constructor.
     */
    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settings = $settingsRepository;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if ($this->user->isGuest()) {
            $this->outPut(ResponseCode::JUMP_TO_LOGIN,'');
        }
        return true;
    }

    public function main()
    {

        $path = $this->inPut("path");
        $width = $this->inPut("width");
        $colorR = $this->inPut("r");
        $colorG = $this->inPut("g");
        $colorB = $this->inPut("b");
        if(empty($path)){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }

        $paramData = [
            'path'=>$path,
            'width'=>$width,
            'r'=>$path,
            'g'=>$path,
            'b'=>$path,
        ];

        if(!(bool)$this->settings->get('miniprogram_app_id', 'wx_miniprogram') || !(bool)$this->settings->get('miniprogram_app_secret', 'wx_miniprogram')){
            $this->outPut(ResponseCode::INVALID_PARAMETER, '请先配置小程序参数');
        }

        try {
            $app = $this->miniProgram();
            $response = $app->app_code->get($path, [
                'width' => $width,
                'line_color' => [
                    'r' => $colorR,
                    'g' => $colorG,
                    'b' => $colorB,
                ],
            ]);
        } catch (\Exception $e) {
            app('errorLog')->info('requestId：' . $this->requestId . '-'.'生成小程序二维码接口异常-WechatMiniProgramCodeController： 入参：'
                . json_encode($paramData) . ';用户id：' . $this->user->id . ';异常：' . $e->getMessage());
            return $this->outPut(ResponseCode::INTERNAL_ERROR, '生成小程序二维码接口异常');
        }
        $response = $response->withoutHeader('Content-disposition');
        $filename = $response->save(storage_path('app/public/miniprogram'));

        $url = $this->request->getUri();
        $port = $url->getPort();
        $port = $port == null ? '' : ':' . $port;
        $path = $url->getScheme() . '://' . $url->getHost() . $port . '/';

        $url = $path."/storage/miniprogram/".$filename;
        return $this->outPut(ResponseCode::SUCCESS,'',$url);
    }
}
