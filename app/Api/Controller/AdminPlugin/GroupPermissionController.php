<?php
/**
 * Copyright (C) 2021 Tencent Cloud.
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

namespace App\Api\Controller\AdminPlugin;

use App\Common\PermissionKey;
use App\Common\ResponseCode;
use App\Models\PluginGroupPermission;
use Discuz\Base\DzqAdminController;

class GroupPermissionController extends DzqAdminController
{
    public function main()
    {
        $appId = $this->inPut('appId');
        $groupId = $this->inPut('groupId');
        $type = $this->inPut('type');//type:0 关闭权限 1：开启权限
        $this->dzqValidate($this->inPut(), [
            'appId' => 'required|string',
            'groupId' => 'required|integer',
            'type' => 'required|integer|in:0,1'
        ]);
        $permission = PluginGroupPermission::query()->where([
            'app_id' => $appId,
            'group_id' => $groupId
        ]);
        if ($type == 0) {
            $permission->delete();
            $this->outPut(0, '关闭插件权限成功');
        } else {
            $permission = $permission->first();
            if (!empty($permission)) {
                $this->outPut(ResponseCode::INVALID_PARAMETER, '权限已存在，不必重复设置');
            }
            $permission = new PluginGroupPermission();
            $permission->setRawAttributes([
                'app_id' => $appId,
                'group_id' => $groupId,
                'permission' => PermissionKey::PLUGIN_INSERT_PERMISSION
            ]);
            if (!$permission->save()) {
                $this->outPut(ResponseCode::DB_ERROR, '开启插件权限失败');
            }
        }
        $this->outPut(0, '开启插件权限成功');
    }
}
