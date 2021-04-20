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
use App\Models\User;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Base\DzqController;
use Illuminate\Support\Arr;

class RealUserController extends DzqController
{
    use AssertPermissionTrait;

    public function main()
    {
        $actor = $this->user;

        $this->assertPermission($actor->id);

        $realName = $this->inPut('realName');
        $identity = $this->inPut('identity');

        $this->user->changeRealname($realName);
        $this->user->changeIdentity($identity);

        $qcloud = $this->app->make('qcloud');
        $res = $qcloud->service('faceid')->idCardVerification($identity, $realName);

        //判断身份证信息与姓名是否符合
        if (Arr::get($res, 'Result', false) != User::NAME_ID_NUMBER_MATCH) {
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }

        $this->user->save();

        $result = [
            'id' => $this->user->id,
            'username' => $this->user->username,
            'mobile' => $this->user->mobile,
            'threadCount' => $this->user->thread_count,
            'followCount' => $this->user->follow_count,
            'fansCount' => $this->user->fans_count,
            'likedCount' => $this->user->liked_count,
            'realname' => $this->user->realname,
            'identity' => $this->user->identity,
            'avatarUrl' => $this->user->avatar,
            'updatedAt' => optional($this->user->updated_at)->format('Y-m-d H:i:s'),
            'createdAt' => optional($this->user->created_at)->format('Y-m-d H:i:s'),
        ];

        $this->outPut(ResponseCode::SUCCESS, '', $result);

    }
}
