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
use App\Models\GroupPaidUser;
use App\Models\GroupUser;
use App\Models\GroupUserMq;
use App\Models\User;
use App\Repositories\UserRepository;
use Discuz\Base\DzqAdminController;
use Discuz\Base\DzqLog;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;

class BatchDeleteGroupsController extends DzqAdminController
{
    protected $bus;

    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $ids = $this->inPut('ids') ?: '';
        $ids = explode(',', $ids);
        return $userRepo->canDeleteGroup($this->user, $ids);
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

        $groupIdDeletes = $groupDatas->pluck('id')->toArray();
        $result = $this->changeUserGroupId($groupIdDeletes);
        if ($result == 'true'){
            /* @var DatabaseManager $dbMgr*/
            $dbMgr = app('db');
            $result = $dbMgr->transaction(function () use ($groupDatas,$dbMgr){
                $paidGroupIds=[];
                $groupDatas->each(function ($group) use(&$paidGroupIds,$dbMgr) {
                    if ($group->is_paid == Group::IS_PAID){
                        $paidGroupIds[] = $group->id;
                    }
                    $group->delete();

                    //调整比该等级大的其他等级
                    Group::query()->where("is_paid",Group::IS_PAID)
                        ->where("level",">",$group->level)
                        ->update(['level'=>$dbMgr->raw("level-1")]);
                });
                GroupPaidUser::query()->whereIn('group_id', $paidGroupIds)->where('delete_type', "=",'0')
                    ->update(['operator_id' => $this->user->id, 'deleted_at' => Carbon::now(), 'delete_type' => GroupPaidUser::DELETE_TYPE_ADMIN]);

                return true;
            });
        }

        if ($result == "true"){
            return $this->outPut(ResponseCode::SUCCESS, '');
        }
        else{
            return $this->outPut(ResponseCode::INTERNAL_ERROR, $result);
        }
    }

    private function changeUserGroupId($groupIdDeletes){
        try {
            $defualtGroup = Group::query()->select('id')->where("default",1)->first();
            $defualtGroupId = Group::MEMBER_ID;
            if (!empty($defualtGroup)){
                $defualtGroupId = $defualtGroup->id;
            }
            $changeUserIds = GroupUser::query()->select("user_id")->whereIn('group_id',$groupIdDeletes)->get();

            $dbMgr = app('db');
            $dbResult = $dbMgr->transaction(function () use ($changeUserIds, $groupIdDeletes, $defualtGroupId, $dbMgr){
                foreach ($changeUserIds as $key=>$item){
                    $user_id = $item->user_id;

                    //减少过期时间
                    $remainDays = GroupUserMq::query()->whereIn('group_id',$groupIdDeletes)->where('user_id',$user_id)->sum('remain_days');
                    User::query()->where('id',$user_id)->update(['expired_at'=>$dbMgr->raw("DATE_ADD(expired_at,interval ".-$remainDays." day)")]);


                    //设置新的用户组
                    $newFeeGroup = GroupUserMq::query()->select("group_id")
                        ->leftJoin("groups as tb2","group_id","tb2.id")
                        ->where("tb2.is_paid",Group::IS_PAID)
                        ->where('user_id', $user_id)
                        ->whereNotIn('group_id',$groupIdDeletes)
                        ->orderByDesc("tb2.level")->first();
                    if (empty($newFeeGroup)){
                        //设置为免费默认组
                        GroupUser::query()->where('user_id',$user_id)->update(['group_id'=>$defualtGroupId]);
                    }else{
                        GroupUser::query()->where('user_id',$user_id)->update(['group_id'=> $newFeeGroup->group_id]);
                    }
                }
                return true;
            });
            if (!$dbResult){
                return false;
            }

            GroupUserMq::query()->whereIn('group_id',$groupIdDeletes)->delete();
            return true;
        }catch (Throwable $e){
            if (empty($e->validator) || empty($e->validator->errors())) {
                $errorMsg = $e->getMessage();
            } else {
                $errorMsg = $e->validator->errors()->first();
            }
            DzqLog::error('delete_user_group_error', [], $errorMsg);
            return $errorMsg;
        }
    }

}
