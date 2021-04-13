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
use App\Common\ResponseCode;
use App\Events\Users\Registered;
use App\Events\Users\RegisteredCheck;
use App\Events\Users\Saving;
use App\Models\Invite;
use App\Models\User;
use App\Validators\UserValidator;
use Carbon\Carbon;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Base\DzqController;
use Discuz\Contracts\Setting\SettingsRepository;
use Discuz\Foundation\EventsDispatchTrait;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;

class RegisterController extends DzqController
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
            $this->outPut(ResponseCode::REGISTER_CLOSE,ResponseCode::$codeMap[ResponseCode::REGISTER_CLOSE]);
        }

        $data = [
            'username' => $this->inPut('username'),
            'password' => $this->inPut('password'),
            'password_confirmation' => $this->inPut('passwordConfirmation'),
            'code' => $this->inPut('code'),
            'register_ip' => ip($this->request->getServerParams()),
            'register_port' => $this->request->getServerParams()['REMOTE_PORT'] ? $this->request->getServerParams()['REMOTE_PORT'] : 0,
            'register_reason' => $this->inPut('registerReason'),
            'captcha_ticket' => $this->inPut('captchaTicket'),
            'captcha_rand_str' => $this->inPut('captchaRandStr'),
        ];

        if (!empty($data['code'])) {
            if (Invite::lengthByAdmin($data['code'])) {
                if (!$exists = Invite::query()->where('code', $data['code'])->exists()) {
                    $this->outPut(ResponseCode::DECRYPT_CODE_FAILURE, ResponseCode::$codeMap[ResponseCode::DECRYPT_CODE_FAILURE]);
                }
            } else {
                if (!$exists = User::query()->find($data['code'])->exists()) {
                    $this->outPut(ResponseCode::DECRYPT_CODE_FAILURE, ResponseCode::$codeMap[ResponseCode::DECRYPT_CODE_FAILURE]);
                }
            }
        }

        // 敏感词校验
        $content = $this->censor->checkText($data['username'], 'username',true);

        $user = User::register([
            'username' => $data['username'],
            'password' => $data['password'],
            'register_ip' => $data['register_ip'],
            'register_port' => $data['register_port'],
            'register_reason' => $data['register_reason']
        ]);

        // 注册验证码(无感模式不走验证码，开启也不走)
        $captcha = '';  // 默认为空将不走验证
        if ((bool)$this->settings->get('register_captcha') &&
            (bool)$this->settings->get('qcloud_captcha', 'qcloud') &&
            ($this->settings->get('register_type', 'default') != 2)) {
            $captcha = [
                $data['captcha_ticket'],
                $data['captcha_rand_str'],
                $data['register_ip'],
            ];
        }

        // 付费模式，默认注册时即到期
        if ($this->settings->get('site_mode') == 'pay') {
            $user->expired_at = Carbon::now();
        }

        // 审核模式，设置注册为审核状态
        if ($this->settings->get('register_validate') || $this->censor->isMod) {
            $user->status = 2;
        }

        $this->events->dispatch(
            new Saving($user, $this->user, $data)
        );

        // 密码为空的时候，不验证密码，允许创建密码为空的用户(但无法登录，只能用其它方法登录)
        $attrs_to_validate = array_merge($user->getAttributes(), [
            'password' => $data['password'],
            'password_confirmation' => $data['password_confirmation'],
            'captcha' => $captcha,
        ]);
        if ($data['password'] === '') {
            unset($attrs_to_validate['password']);
        }
        unset($attrs_to_validate['register_reason']);
        $this->validator->valid($attrs_to_validate);

        $user->save();
        $user->raise(new Registered($user, $this->user, $data));

        $this->dispatchEventsFor($user, $this->user);

        // 注册后的登录检查
        if (!(bool)$this->settings->get('register_validate')) {
            $this->events->dispatch(new RegisteredCheck($user));
        }

        $response = $this->bus->dispatch(
            new GenJwtToken(['username' => $data['username']])
        );

        return $this->outPut(ResponseCode::SUCCESS, '', $this->getName(json_decode($response->getBody())));
    }


    //修改为小驼峰
    private function getName($accessToken){
        return [
            'tokenType' => $accessToken->token_type,
            'expiresIn' => $accessToken->expires_in,
            'accessToken' => $accessToken->access_token,
            'refreshToken' => $accessToken->refresh_token,
        ];
    }
}
