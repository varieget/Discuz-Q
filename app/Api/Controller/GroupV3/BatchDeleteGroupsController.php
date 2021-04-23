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
use App\Models\Group;
use Discuz\Base\DzqController;
use Discuz\Auth\AssertPermissionTrait;
use Illuminate\Contracts\Bus\Dispatcher;

class BatchDeleteGroupsController extends DzqController
{
    use AssertPermissionTrait;
    protected $bus;

    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    public function main()
    {
        $id = $this->inPut('ids');
        if(empty($id)){
            return $this->outPut(ResponseCode::INVALID_PARAMETER, '未获取到必要参数');
        }

        $ids = explode(',', $id);

        foreach ($ids as $id){
            if($id < 1){
                return $this->outPut(ResponseCode::INVALID_PARAMETER);
            }
            $groupRecord = Group::query()->where('id',$id)->first();
            if(!$groupRecord){
                $this->outPut(ResponseCode::INVALID_PARAMETER,'记录不存在');
            }
        }
        $groupDatas = Group::query()->whereIn('id', $ids)->get();

        $groupDatas->each(function ($group) {
            $group->delete();
        });

        return $this->outPut(ResponseCode::SUCCESS, '');
    }


}
