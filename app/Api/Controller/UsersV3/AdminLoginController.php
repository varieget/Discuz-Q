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
use App\Common\ResponseCode;
use App\Events\Users\Logind;
use App\Models\User;
use App\Passport\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Discuz\Foundation\Application;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;

class AdminLoginController extends DzqController
{
    protected $bus;
    protected $app;
    protected $events;

    public function __construct(Dispatcher $bus, Application $app, Events $events)
    {
        $this->bus = $bus;
        $this->app = $app;
        $this->events = $events;
    }

    protected function checkRequestPermissions(\App\Repositories\UserRepository $userRepo)
    {
        return true;
    }
    /**
     * @return array|mixed
     */
    public function main()
    {
        $data = [
            'username' => $this->inPut('username'),
            'password' => $this->inPut('password'),
        ];

        $this->dzqValidate($data, [
            'username' => 'required',
            'password' => 'required',
        ]);

        $exists = User::query()->where(['username'=>$data['username'],'password'=>$data['password']])->exists();
        if (!$exists) {
            return $this->outPut(ResponseCode::NET_ERROR,'','用户名或密码错误');
        }

        $response = $this->bus->dispatch(
            new GenJwtToken($data)
        );

        $accessToken = json_decode($response->getBody());

        if ($response->getStatusCode() === 200) {
            /** @var User $user */
            $user = $this->app->make(UserRepository::class)->getUser();

            $this->events->dispatch(new Logind($user));
        }

        return $this->outPut(ResponseCode::SUCCESS,'',$this->camelData(collect($accessToken)));
    }
}
