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

namespace App\Listeners\User;

use App\Common\ResponseCode;
use Carbon\Carbon;
use Discuz\Common\Utils;
use App\Models\User;
use Discuz\Contracts\Setting\SettingsRepository;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Http\DiscuzResponseFactory;

class BanLogin
{
    public function handle($event)
    {
        $user = $event->user;
        switch ($user->status) {
            case 0:
                $settings = app(SettingsRepository::class);
                if ($user->status == 0
                    && $settings->get('site_mode') == 'pay'
                    && $event->user->expired_at < Carbon::now()
                ) {
                    Utils::outPut(ResponseCode::JUMP_TO_PAY_SITE);
                }
                break;
            case 1:
                Utils::outPut(ResponseCode::USER_BAN);
                throw new PermissionDeniedException('ban_user');
                break;
            case 2:
                Utils::outPut(ResponseCode::USER_IN_REVIEW);
                break;
            case 3:
                Utils::outPut(ResponseCode::VALIDATE_REJECT,ResponseCode::$codeMap[ResponseCode::VALIDATE_REJECT],User::getUserReject($user->id));
                $response = [
                    'errors' => [
                        [
                            'status' => '401',
                            'code' => 'validate_reject',
                            'data' => User::getUserReject($user->id)
                        ]
                    ]
                ];
                return DiscuzResponseFactory::JsonResponse($response)->withStatus(401);
//                throw new PermissionDeniedException('validate_reject');
                break;
            case 4:
                Utils::outPut(ResponseCode::VALIDATE_IGNORE);
                throw new PermissionDeniedException('validate_ignore');
                break;
            case 10:
                Utils::outPut(ResponseCode::USER_NEED_SIGNIN_FIELDS);
                break;
        }
    }
}
