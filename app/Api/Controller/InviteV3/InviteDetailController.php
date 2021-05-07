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

namespace App\Api\Controller\InviteV3;

use App\Common\ResponseCode;
use App\Models\Group;
use App\Models\Invite;
use App\Models\Permission;
use App\Models\User;
use Discuz\Base\DzqController;

class InviteDetailController extends DzqController
{

    public function main()
    {
        $code = $this->inPut('code');

        if (empty($code)) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '缺少必要参数！', '');
        }

        $codeLen = mb_strlen($code, 'utf-8');
        if ($codeLen !== Invite::INVITE_GROUP_LENGTH) {
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }

        $inviteData = Invite::query()
                ->where('code', $code)
                ->first();
        $inviteData = $inviteData->toArray();
        $inviteData['inviteId'] = $inviteData['id'];
        unset($inviteData['id']);
        $inviteData['user'] = User::query()
            ->select('id AS userId', 'nickname', 'avatar')
            ->where('id', $inviteData['user_id'])
            ->first()->toArray();
        $inviteData['group'] = Group::query()
            ->select('id AS groupId', 'name as groupName', 'default')
            ->where('id', $inviteData['group_id'])
            ->first()->toArray();
        $inviteData['group']['groupPermissions'] = Permission::query()
            ->where('group_id', $inviteData['group_id'])
            ->get()->toArray();
        $inviteData = $this->camelData($inviteData);

        return $this->outPut(ResponseCode::SUCCESS, '', $inviteData);
    }
}
