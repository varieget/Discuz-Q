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

namespace App\Api\Controller\GroupV3;

use App\Common\ResponseCode;
use App\Models\Permission;
use App\Repositories\GroupRepository;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Base\DzqController;

class ResourcePermissionsController extends DzqController
{
    use AssertPermissionTrait;

    public function main()
    {
        $actor = $this->user;
        $this->assertRegistered($actor);
        $id = $this->inPut('id');
        if(!$id){
            $this->outPut(ResponseCode::INVALID_PARAMETER,'');
        }

        $query = GroupRepository::query();
        $groupData = $query->where('id',$id)->with(['permission'])->first();

        if(empty($groupData)){
            $this->outPut(ResponseCode::INVALID_PARAMETER, 'ID为'.$id.'记录不存在');
        }

        $group_permission = $groupData->getRelation("permission")->toArray();
        $data = [];
        foreach ($group_permission as $val){
            $data[] = $val['permission'];
        }

        $thread_permission = Permission::THREAD_PERMISSION;
        $judge_permissions = array_intersect($data,$thread_permission);

        $permission = $this->inPut('permission');
        if (in_array($permission,$judge_permissions))
        {
            $this->outPut(ResponseCode::SUCCESS, '',true);
        } else {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '',false);
        }

        return $this->outPut(ResponseCode::SUCCESS, '',$judge_permissions);
    }

}