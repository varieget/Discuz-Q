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


use App\Censor\Censor;
use App\Commands\Users\GenJwtToken;
use App\Commands\Users\RegisterUser;
use App\Common\ResponseCode;
use App\Events\Users\Registered;
use App\Events\Users\RegisteredCheck;
use App\Events\Users\Saving;
use App\Events\Users\TransitionBind;
use App\Models\Invite;
use App\Models\SessionToken;
use App\Models\User;
use App\Notifications\Messages\Wechat\RegisterWechatMessage;
use App\Notifications\System;
use App\Validators\UserValidator;
use Carbon\Carbon;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Base\DzqController;
use Discuz\Contracts\Setting\SettingsRepository;
use Discuz\Foundation\EventsDispatchTrait;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Support\Arr;

class RegisterController extends AuthBaseController
{
    use EventsDispatchTrait;
    use AssertPermissionTrait;

    protected $bus;

    protected $settings;

    protected $events;

    protected $censor;

    protected $validator;

    public function __construct(Dispatcher $bus, SettingsRepository $settings, Events $events, Censor $censor, UserValidator $validator)
    {
        $this->bus = $bus;
        $this->settings = $settings;
        $this->events = $events;
        $this->censor = $censor;
        $this->validator = $validator;

    }


    public function main()
    {
        if (!(bool)$this->settings->get('register_close')) {
            $this->outPut(ResponseCode::REGISTER_CLOSE);

        }
        if((bool)$this->settings->get('qcloud_sms', 'qcloud')
            || (bool)$this->settings->get('offiaccount_close', 'wx_offiaccount')
            || (bool)$this->settings->get('miniprogram_close', 'wx_miniprogram')) {
            $this->outPut(ResponseCode::REGISTER_CLOSE, '请使用微信或者手机号注册登录');
        }


        $data = [
            'username'              => $this->inPut('username'),
            'password'              => $this->inPut('password'),
            'password_confirmation' => $this->inPut('passwordConfirmation'),
            'nickname'              => $this->inPut('nickname'),
            'code'                  => $this->inPut('code'),
            'register_ip'           => ip($this->request->getServerParams()),
            'register_port'         => $this->request->getServerParams()['REMOTE_PORT'] ? $this->request->getServerParams()['REMOTE_PORT'] : 0,
            'captcha_ticket'        => $this->inPut('captchaTicket'),
            'captcha_rand_str'      => $this->inPut('captchaRandStr'),
        ];

        $user = $this->bus->dispatch(
            new RegisterUser($this->request->getAttribute('actor'), $data)
        );

        // 注册后的登录检查
        if (!(bool)$this->settings->get('register_validate')) {
            $this->events->dispatch(new RegisteredCheck($user));
        }
        GenJwtToken::setUid($user->id);
        $response = $this->bus->dispatch(
            new GenJwtToken(Arr::only($data, 'username'))
        );
        return $this->outPut(ResponseCode::SUCCESS,
                             '',
                             $this->addUserInfo($user, $this->camelData(json_decode($response->getBody(),true)))
        );
    }
}
