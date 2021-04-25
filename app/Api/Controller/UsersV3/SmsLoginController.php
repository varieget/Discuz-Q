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
use App\Commands\Users\RegisterPhoneUser;
use App\Common\AuthUtils;
use App\Common\ResponseCode;
use App\Events\Users\Logind;
use App\Models\SessionToken;
use App\Models\User;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Support\Arr;

class SmsLoginController extends AuthBaseController
{
    protected $bus;
    protected $settings;
    protected $events;

    public function __construct(
        Dispatcher          $bus,
        SettingsRepository  $settings,
        Events              $events
    ){
        $this->bus      = $bus;
        $this->settings = $settings;
        $this->events   = $events;
    }

    public function main()
    {
        $type       = $this->inPut('type');
        $mobileCode = $this->getMobileCode('login');
        $inviteCode = $this->inPut('inviteCode');
        $ip         = ip($this->request->getServerParams());
        $port       = Arr::get($this->request->getServerParams(), 'REMOTE_PORT', 0);

        //register new user
        if (is_null($mobileCode->user)) {
            if (!(bool)$this->settings->get('register_close')) {
                $this->outPut(ResponseCode::REGISTER_CLOSE);
            }

            $data['register_ip']    = $ip;
            $data['register_port']  = $port;
            $data['mobile']         = $mobileCode->mobile;
            $data['code']           = $inviteCode;
            $user = $this->bus->dispatch(
                new RegisterPhoneUser($this->user, $data)
            );
            $mobileCode->setRelation('user', $user);

            $this->updateUserBindType($mobileCode->user,AuthUtils::PHONE);
        }

        //手机号登录需要填写扩展字段审核的场景
        if($mobileCode->user->status != User::STATUS_MOD){
            $this->events->dispatch(
                new Logind($mobileCode->user)
            );
        }

        //login
        $params = [
            'username' => $mobileCode->user->username,
            'password' => ''
        ];

        $response = $this->bus->dispatch(
            new GenJwtToken($params)
        );

        $accessToken = json_decode($response->getBody(), true);

        $result = $this->camelData(collect($accessToken));

        $result = $this->addUserInfo($mobileCode->user, $result);

        if($type == 'mobilebrowser_sms_login') {
            $wechat     = (bool)$this->settings->get('offiaccount_close', 'wx_offiaccount');
            $miniWechat = (bool)$this->settings->get('miniprogram_close', 'wx_miniprogram');
            $sms        = (bool)$this->settings->get('qcloud_sms', 'qcloud');
            //短信，微信，小程序均未开启
            if(! $sms && !$wechat && !$miniWechat ) {
                $this->outPut(ResponseCode::SUCCESS, '', $result);
            }

            //手机浏览器登录，需要做绑定前准备
            $token = SessionToken::generate(SessionToken::WECHAT_MOBILE_BIND,
                                            $accessToken ,
                                            $mobileCode->user->id
            );
            //返回昵称，用于前端绑定微信时展示
            $nickname = !empty($mobileCode->user->nickname) ? $mobileCode->user->nickname : $mobileCode->user->mobile;

            $data = array_merge($this->camelData($accessToken),[
                'sessionToken'  => $token->token,
                'nickname'      => $nickname
            ]);
            $token->save();
            if($wechat || $miniWechat) { //开了微信，
                //未绑定微信
                $bindTypeArr = AuthUtils::getBindTypeArrByCombinationBindType($mobileCode->user->bind_type);
                if(!in_array(AuthUtils::WECHAT, $bindTypeArr)) {
                    return $this->outPut(ResponseCode::NEED_BIND_WECHAT, '', $data);
                }
            }
        }

        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }
}