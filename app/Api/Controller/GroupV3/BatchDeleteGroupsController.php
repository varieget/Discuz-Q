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
use App\Models\GroupUser;
use App\Models\GroupUserMq;
use App\Models\User;
use App\Repositories\UserRepository;
use Discuz\Base\DzqAdminController;
use Discuz\Base\DzqLog;
use Illuminate\Contracts\Bus\Dispatcher;

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
            $groupDatas->each(function ($group) {
                $group->delete();
            });
            return $this->outPut(ResponseCode::SUCCESS, '');
        }else{
            return $this->outPut(ResponseCode::INTERNAL_ERROR, $result);
        }
    }

    private function changeUserGroupId($groupIdDeletes){
        $do_userIds=[];
        try {
            $defualtGroup = Group::query()->select('id')->where("default",1)->first();
            $defualtGroupId = Group::MEMBER_ID;
            if (!empty($defualtGroup)){
                $defualtGroupId = $defualtGroupId->id;
            }
            $changeUserIds = GroupUser::query()->select("user_id")->whereIn('group_id',$groupIdDeletes)->get();

            $doClosure = function () use ($changeUserIds, $groupIdDeletes, $defualtGroupId){
                foreach ($changeUserIds as $key=>$item){
                    $user_id = $item->user_id;

                    //减少过期时间
                    $remainDays = GroupUserMq::query()->whereIn('group_id',$groupIdDeletes)->sum('remain_days');
                    User::query()->where('id',$user_id)->update(['expired_at'=>'date_add(`expired_at`,interval '.-$remainDays.' day)']);

                    //设置新的用户组
                    $newFeeGroup = GroupUserMq::query()->select("tb1.group_id")
                        ->leftJoin("groups as tb2","tb1.group_id","tb2.id")
                        ->where("tb2.is_paid",Group::IS_PAID)
                        ->where('tb1.user_id', $user_id)
                        ->whereNotIn('tb1.group_id',$groupIdDeletes)
                        ->orderByDesc("tb2.level")->first();
                    if (empty($newFeeGroup)){
                        //设置为免费默认组
                        GroupUser::query()->where('user_id',$user_id)->update('group_id',$defualtGroupId);
                    }else{
                        GroupUser::query()->where('user_id',$user_id)->update('group_id',$newFeeGroup->group_id);
                    }
                    $do_userIds[] = $user_id;
                }
            };
            $doClosure();

            GroupUserMq::query()->whereIn('group_id',$groupIdDeletes)->delete();
            return true;
        }catch (Throwable $e){
            GroupUserMq::query()->whereIn('group_id',$groupIdDeletes)->whereIn('user_id',$do_userIds)->delete();

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
