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
use App\Commands\Users\RegisterUser;
use App\Common\ResponseCode;
use App\Events\Users\RegisteredCheck;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Database\ConnectionInterface;

class RegisterController extends AuthBaseController
{
    public $bus;
    public $settings;
    public $events;
    public $censor;
    public $userValidator;
    public $connection;

    public function __construct(
        Dispatcher          $bus,
        SettingsRepository  $settings,
        Events              $events,
        Censor              $censor,
        UserValidator       $userValidator,
        ConnectionInterface $connection
    ){
        $this->bus              = $bus;
        $this->settings         = $settings;
        $this->events           = $events;
        $this->censor           = $censor;
        $this->userValidator    = $userValidator;
        $this->connection       = $connection;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return true;
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

        $this->connection->beginTransaction();
        try {
            $this->dzqValidate($data, [
                'username' => 'required',
                'password' => 'required',
                'nickname' => 'required',
            ]);

            //用户名校验
            $result = strpos($data['username'],' ');
            if ($result !== false) {
                $this->connection->rollback();
                $this->outPut(ResponseCode::USERNAME_NOT_ALLOW_HAS_SPACE);
            }

            //重名校验
            $user = User::query()->where('username',$data['username'])->lockForUpdate()->first();
            if (!empty($user)) {
                $this->connection->rollback();
                $this->outPut(ResponseCode::USERNAME_HAD_EXIST);
            }

            $this->censor->checkText($data['username'], 'username');
            //密码校验
            $result = strpos($data['password'],' ');
            if ($result !== false) {
                $this->connection->rollback();
                $this->outPut(ResponseCode::PASSWORD_NOT_ALLOW_HAS_SPACE);
            }
            //昵称校验
            $this->censor->checkText($data['nickname'], 'nickname');

            $user = $this->bus->dispatch(
                new RegisterUser($this->request->getAttribute('actor'), $data)
            );

            // 注册后的登录检查
            if (!(bool)$this->settings->get('register_validate')) {
                $this->events->dispatch(new RegisteredCheck($user));
            }

            $accessToken = $this->getAccessToken($user);
            $this->connection->commit();
            return $this->outPut(ResponseCode::SUCCESS,
                                 '',
                                 $this->camelData($this->addUserInfo($user, $accessToken))
            );
        } catch (\Exception $e) {
            app('errorLog')->info('requestId：' . $this->requestId . '-' . '用户名: "' . $data['username'] . '" 注册接口异常-RegisterController： ' . $e->getMessage());
            $this->connection->rollback();
            return $this->outPut(ResponseCode::INTERNAL_ERROR, '用户名注册接口异常');
        }

    }
}
