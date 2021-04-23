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
use App\Models\SessionToken;
use Illuminate\Contracts\Bus\Dispatcher;

class SmsBindController extends AuthBaseController
{
    protected $bus;

    public function __construct(
        Dispatcher          $bus
    ){
        $this->bus      = $bus;
    }

    public function main()
    {
        $mobileCode     = $this->getMobileCode('bind');
        $sessionToken   = $this->inPut('sessionToken');
        $token          = SessionToken::get($sessionToken);
        $actor          = !empty($token->user) ? $token->user : $this->user;

        // 判断手机号是否已经被绑定
        if (!empty($actor->mobile)) {
            $this->outPut(ResponseCode::MOBILE_IS_ALREADY_BIND);
        }

        if ($actor->exists) {
            $actor->changeMobile($mobileCode->mobile);
            $actor->save();

            $this->updateUserBindType($actor, AuthUtils::PHONE);

            //用于用户名登录绑定手机号使用
            if (!empty($token->user)) {
                //token生成
                $params = [
                    'username' => $actor->username,
                    'password' => ''
                ];
                GenJwtToken::setUid($actor->id);
                $response = $this->bus->dispatch(
                    new GenJwtToken($params)
                );

                $accessToken = json_decode($response->getBody(), true);
                $result = $this->camelData(collect($accessToken));
                $result = $this->addUserInfo($actor, $result);

                $this->outPut(ResponseCode::SUCCESS, '', $result);
            }

            $this->outPut(ResponseCode::SUCCESS, '', []);
        }

        $this->outPut(ResponseCode::INVALID_PARAMETER);
    }
}
