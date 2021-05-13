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

class ListDenyUserController extends DzqController
{
    use AssertPermissionTrait;

    private $databaseField = [
        'id',
        'updated_at',
        'created_at'
    ];

    public function main()
    {
        $actor = $this->user;

        $this->assertRegistered($actor);

        $sort = $this->inPut('sort') ? $this->inPut('sort') : 'id';
        $currentPage = $this->inPut('page');
        $perPage = $this->inPut('perPage');

        $order = 'asc';
        if (strstr($sort,'-')){
            $sort = substr($sort, 1);
            $order = 'desc';
        }

        if (!in_array($sort,$this->databaseField)) {
            return $this->outPut(ResponseCode::INVALID_PARAMETER,'');
        }


        $query = User::query();
        $query->leftJoin('deny_users', 'id', '=', 'deny_user_id')
            ->where('user_id', $actor->id);
        $query->select('deny_users.user_id','deny_users.deny_user_id','users.id AS pid', 'users.username', 'users.avatar');

        $query->orderBy($sort, $order);

        $users = $this->pagination($currentPage, $perPage, $query);
        $data = $this->camelData($users);

        return $this->outPut(ResponseCode::SUCCESS,'', $data);
    }
}
