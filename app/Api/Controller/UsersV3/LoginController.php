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
use App\Passport\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Discuz\Foundation\Application;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;

class LoginController extends DzqController
{

    protected $bus;

    protected $app;

    protected $events;

    public function __construct(
        Dispatcher $bus,
        Application $app,
        Events $events
    )
    {
        $this->bus = $bus;
        $this->app = $app;
        $this->events = $events;
    }

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

        $response = $this->bus->dispatch(
            new GenJwtToken($data)
        );

        $accessToken = json_decode($response->getBody());

        if ($response->getStatusCode() === 200) {
            $user = $this->app->make(UserRepository::class)->getUser();

            $this->events->dispatch(new Logind($user));
        }

        return $this->outPut(ResponseCode::SUCCESS, '', $this->getName($accessToken));
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
