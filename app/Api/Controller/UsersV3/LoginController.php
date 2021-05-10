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


use App\Commands\Users\GenJwtToken;
use App\Common\AuthUtils;
use App\Common\ResponseCode;
use App\Common\Utils;
use App\Events\Users\Logind;
use App\Events\Users\TransitionBind;
use App\Models\SessionToken;
use App\Passport\Repositories\UserRepository;
use App\Settings\SettingsRepository;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Validation\Factory as Validator;
use Discuz\Foundation\Application;

class LoginController extends AuthBaseController
{
    protected $bus;

    protected $app;

    protected $events;

    protected $type;

    protected $validator;

    protected $setting;

    public function __construct(
        Dispatcher $bus,
        Application $app,
        Events $events,
        Validator $validator,
        SettingsRepository $settingsRepository
    )
    {
        $this->bus = $bus;
        $this->app = $app;
        $this->events = $events;
        $this->validator = $validator;
        $this->setting = $settingsRepository;
    }

    public function main()
    {
        $data = [
            'username' => $this->inPut('username'),
            'password' => $this->inPut('password'),
        ];

        $this->validator->make($data, [
            'username' => 'required',
            'password' => 'required',
        ])->validate();

        $type = $this->inPut('type');
        $response = $this->bus->dispatch(
            new GenJwtToken($data)
        );
        if($response->getStatusCode() != 200) {
            return $this->outPut(ResponseCode::LOGIN_FAILED, '登录失败');
        }
        $accessToken = json_decode($response->getBody(), true);

        $user = $this->app->make(UserRepository::class)->getUser();

        $this->events->dispatch(new Logind($user));

        $wechat = (bool)$this->setting->get('offiaccount_close', 'wx_offiaccount');
        $miniWechat = (bool)$this->setting->get('miniprogram_close', 'wx_miniprogram');
        $sms = (bool)$this->setting->get('qcloud_sms', 'qcloud');
        //短信，微信，小程序均未开启
        if(! $sms && !$wechat && !$miniWechat ) {
            return $this->outPut(ResponseCode::SUCCESS, '', $this->addUserInfo($user,$this->camelData($accessToken)));
        }

        //过渡时期微信绑定用户名密码登录的用户
        $sessionToken = $this->inPut('sessionToken');
        if($sessionToken && strlen($sessionToken) != 0 && (bool)$this->setting->get('is_need_transition')) {
            $this->events->dispatch(new TransitionBind($user, ['sessionToken' => $sessionToken]));
            return $this->outPut(ResponseCode::SUCCESS, '', $this->addUserInfo($user,$this->camelData($accessToken)));
        }

        if($type == 'mobilebrowser_username_login') {
            //手机浏览器登录，需要做绑定前准备
            $token = SessionToken::generate(SessionToken::WECHAT_MOBILE_BIND, $accessToken , $user->id);
            $token->save();
            $data = array_merge($this->camelData($accessToken),[
                'sessionToken'  => $token->token,
                'nickname'      => $user->nickname
            ]);
            if($wechat || $miniWechat) { //开了微信，
                //未绑定微信
                $bindTypeArr = AuthUtils::getBindTypeArrByCombinationBindType($user->bind_type);
                if(!in_array(AuthUtils::WECHAT, $bindTypeArr)) {
                    return $this->outPut(ResponseCode::NEED_BIND_WECHAT, '', $data);
                }
            }
            if(! $wechat && ! $miniWechat && $sms && !$user->mobile) {//开了短信配置未绑定手机号
                return $this->outPut(ResponseCode::NEED_BIND_PHONE, '', $data);
            }
            return $this->outPut(ResponseCode::SUCCESS, '', $this->addUserInfo($user,$this->camelData($accessToken)));
        }
        return $this->outPut(ResponseCode::SUCCESS, '', $this->addUserInfo($user,$this->camelData($accessToken)));
    }
}
