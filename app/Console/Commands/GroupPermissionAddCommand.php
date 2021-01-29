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

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Permission;
use App\Models\Thread;
use Discuz\Console\AbstractCommand;

class GroupPermissionAddCommand extends AbstractCommand
{
    protected $signature = 'grouppermission:add';

    protected $description = '添加内容付费的用户组权限';

    public function handle()
    {
        $newPermission = [
            'createThread.' . Thread::TYPE_OF_TEXT . '.position',
            'createThread.' . Thread::TYPE_OF_LONG . '.position',
            'createThread.' . Thread::TYPE_OF_VIDEO . '.position',
            'createThread.' . Thread::TYPE_OF_IMAGE . '.position',
            'createThread.' . Thread::TYPE_OF_AUDIO . '.position',
            'createThread.' . Thread::TYPE_OF_QUESTION . '.position',
            'createThread.' . Thread::TYPE_OF_GOODS . '.position'
        ];

        $permission = Permission::query()
            ->where('permission', 'like', 'createThread%')
            ->where('group_id', Group::MEMBER_ID)
            ->get();
        $permission = $permission->toArray();

        $existPermission = array();
        foreach ($permission as $permission_key => $permission_val) {
            array_push($existPermission ,$permission_val['permission']);
        }

        foreach ($newPermission as $key => $val) {
            if (!in_array($val, $existPermission)) {
                Permission::query()->insert(['group_id' => Group::MEMBER_ID, 'permission' => $val]);
            }
        }

    }
}
