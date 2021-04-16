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
use Discuz\Base\DzqController;

class ResourceGroupsController extends DzqController
{

    /**
     * {@inheritdoc}
     */
    public $optionalInclude = [
        'permission',
        'categoryPermissions',
    ];


    public function main()
    {
        $id = $this->inPut('id');

        if(!$id){
            $this->outPut(ResponseCode::INVALID_PARAMETER,'');
        }

        $include = $this->inPut('include');
        $query = GroupRepository::query();
        $groupData = $query->where('id',$id)->first();
        if(empty($groupData)){
            $this->outPut(ResponseCode::INVALID_PARAMETER, 'ID为'.$id.'记录不存在');
        }
        $include = [$include];
        if (Permission::query()) {
            // 是否包含分类权限
            if (in_array('categoryPermissions', $include)) {
                $query->with(['permission']);
            } else {
                $query->with(['permission' => function ($query) {
                    $query->where('permission', 'not like', 'category%')
                        ->where('permission', 'not like', 'switch.%');
                }]);
            }
        }

        $result = $this->camelData($query->where('id', $id)->first());


        return $this->outPut(ResponseCode::SUCCESS, '',$result);
    }
}
