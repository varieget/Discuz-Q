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
use App\Events\Users\TransitionBind;
use App\Models\SessionToken;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Support\Arr;
use Discuz\Contracts\Setting\SettingsRepository;

/**
 * 过渡时期，微信绑定手机
 * Class WechatTransitionBindSmsController
 * @package App\Api\Controller\UsersV3
 */
class WechatTransitionBindSmsController extends AuthBaseController
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

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return true;
    }

    public function main()
    {
        //过渡开关未打开
        if(!(bool)$this->settings->get('is_need_transition')) {
            $this->outPut(ResponseCode::TRANSITION_NOT_OPEN);
        }
        //未开启短信
        if(!(bool)$this->settings->get('qcloud_cos', 'qcloud')) {
            $this->outPut(ResponseCode::SMS_NOT_OPEN);
        }
        $mobileCode = $this->getMobileCode('login');
        $inviteCode = $this->inPut('inviteCode');
        $sessionToken = $this->inPut('sessionToken');

        //register new user
        if (is_null($mobileCode->user)) {
            if (!(bool)$this->settings->get('register_close')) {
                $this->outPut(ResponseCode::REGISTER_CLOSE);
            }

            $data['register_ip']    = ip($this->request->getServerParams());;
            $data['register_port']  = Arr::get($this->request->getServerParams(), 'REMOTE_PORT', 0);
            $data['mobile']         = $mobileCode->mobile;
            $data['code']           = $inviteCode;
            $user = $this->bus->dispatch(
                new RegisterPhoneUser($this->user, $data)
            );
            $mobileCode->setRelation('user', $user);

            $this->updateUserBindType($mobileCode->user,AuthUtils::PHONE);
        }

        //手机用户绑定微信操作
        $this->events->dispatch(new TransitionBind($mobileCode->user, ['sessionToken' => $sessionToken]));

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
        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }
}
