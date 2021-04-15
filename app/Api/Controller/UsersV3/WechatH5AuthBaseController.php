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
use App\Models\MobileCode;
use App\Models\SessionToken;
use App\Models\User;
use App\Repositories\MobileCodeRepository;
use Illuminate\Support\Carbon;

abstract class WechatH5AuthBaseController extends AuthBaseController
{
    public function getCommonParam()
    {
        $code           = $this->inPut('code');
        $sessionId      = $this->inPut('sessionId');
        $sessionToken   = $this->inPut('sessionToken');//PC扫码使用

        $request = $this->request   ->withAttribute('session', new SessionToken())
                                    ->withAttribute('sessionId', $sessionId);

        $this->dzqValidate([
                               'code'      => $code,
                               'sessionId' => $sessionId,
                           ], [
                               'code'      => 'required',
                               'sessionId' => 'required'
                           ]);

        $this->socialite->setRequest($request);

        $driver = $this->socialite->driver('wechat');
        $wxuser = $driver->user();

        /** @var User $actor */
        $actor = $this->user;

    }

}
