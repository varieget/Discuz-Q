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
use App\Models\Invite;
use App\Models\GroupUser;
use App\Models\User;
use App\Models\Order;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Base\DzqController;

class InviteUsersListController extends DzqController
{
    use AssertPermissionTrait;

    public function main()
    {
        $currentPage = $this->inPut('page');
        $perPage = $this->inPut('perPage');

        $query = Invite::query();
        $query->select('invites.user_id', 'invites.to_user_id', 'users.avatar', 'users.joined_at');
        $query->join('users', 'users.id', '=', 'invites.user_id');
        $query->where('invites.user_id', $this->user->id);
        $query->where('invites.status', Invite::STATUS_USED);

        $inviteUsersList = $this->pagination($currentPage, $perPage, $query);
        $inviteData = $inviteUsersList['pageData'] ?? [];
        $userIds = array_column($inviteData, 'to_user_id');
        $users = User::instance()->getUsers($userIds);
        $users = array_column($users, null, 'id');

        $registOrderDatas = Order::query()
            ->whereIn('user_id', $userIds)
            ->where(['type' => Order::ORDER_TYPE_REGISTER, 'status' => Order::ORDER_STATUS_PAID])
            ->get()->toArray();
        $registOrderDatas = array_column($registOrderDatas, null, 'user_id');
        foreach ($inviteData as $key => $value) {
            $inviteData[$key]['nickname'] = $users[$value['to_user_id']]['nickname'] ?? '';
            $inviteData[$key]['bounty'] = 0;
            if (isset($registOrderDatas[$value['user_id']])) {
                $inviteData[$key]['bounty'] = floatval($registOrderDatas[$value['user_id']]['third_party_amount']);
            }
        }

        $groups = GroupUser::instance()->getGroupInfo([$this->user->id]);
        $groups = array_column($groups, null, 'user_id');

        $result = array(
            'userId' => $this->user->id,
            'nickname' => $this->user->nickname,
            'avatar' => $this->user->avatar,
            'groupName' => $groups[$this->user->id]['groups']['name'],
            'totalInviteUsers' => count($inviteData),
            'totalInviteBounties' => array_sum(array_column($inviteData, 'bounty')),
            'inviteUsersList' => $inviteData
        );
        $inviteUsersList['pageData'] = $result;
        return $this->outPut(ResponseCode::SUCCESS, '', $this->camelData($inviteUsersList));
    }
}
