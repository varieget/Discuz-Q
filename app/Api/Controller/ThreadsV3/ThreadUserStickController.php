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

namespace App\Api\Controller\ThreadsV3;


use App\Common\CacheKey;
use App\Common\ResponseCode;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Thread;
use App\Models\ThreadUserStickRecord;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Settings\SettingsRepository;
use Discuz\Base\DzqCache;
use Discuz\Base\DzqController;
use Illuminate\Support\Carbon;

class ThreadUserStickController extends DzqController
{
    use ThreadTrait;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if ($this->user->isGuest()) {
            $this->outPut(ResponseCode::JUMP_TO_LOGIN);
        }
        if ($this->user->status == User::STATUS_NEED_FIELDS) {
            $this->outPut(ResponseCode::JUMP_TO_SIGIN_FIELDS);
        }
        if ($this->user->status == User::STATUS_MOD) {
            $this->outPut(ResponseCode::JUMP_TO_AUDIT);
        }

        return true;
    }

    public function main()
    {
        $user = $this->user;
        $thread_id = $this->inPut('threadId');
        $status = $this->inPut('status'); //0 取消 1 置顶

        if (empty($thread_id)) return $this->outPut(ResponseCode::INVALID_PARAMETER);

        $threadRow = Thread::query()->where('id', $thread_id)->first();
        if (empty($threadRow)) {
            return $this->outPut(ResponseCode::INVALID_PARAMETER, "主题id" . $thread_id . "不存在");
        }
        if ($threadRow->user_id != $user->id){
            return $this->outPut(ResponseCode::INVALID_PARAMETER, "主题id" . $thread_id . "不属于自己");
        }

        $userStick = ThreadUserStickRecord::query()->where("stick_user_id",'=', $user->id)->first();
        if(empty($userStick)){
            if ($status == ThreadUserStickRecord::status_no){
                //没有置顶的，不能取消
               // return $this->outPut(ResponseCode::INVALID_PARAMETER, "没有置顶，不需要取消");
            }else{
                //插入数据库
                $userStickAdd = new ThreadUserStickRecord();
                $userStickAdd->setAttribute("stick_user_id",$user->id);
                $userStickAdd->setAttribute("stick_thread_id", $thread_id);
                $userStickAdd->setAttribute("stick_status", ThreadUserStickRecord::status_yes);
                $userStickAdd->save();

                //清thread缓存
                $this->clearThreadCach($thread_id);
            }
        }else{
            if ($status == ThreadUserStickRecord::status_no){
                if ($thread_id != $userStick->stick_thread_id){
                    return $this->outPut(ResponseCode::INVALID_PARAMETER, "取消置顶与当前置顶的不是同一个");
                }else{
                    ThreadUserStickRecord::query()->where("stick_user_id",'=', $user->id)->delete();
                    //清thread缓存
                    $this->clearThreadCach($thread_id);
                }
            }else{
                if ($thread_id != $userStick->stick_thread_id) {
                    ThreadUserStickRecord::query()->where("stick_user_id", '=', $user->id)
                        ->update(['stick_thread_id' => $thread_id,
                            'stick_status' => ThreadUserStickRecord::status_yes,
                            'updated_at' => Carbon::now()]);
                    //清thread缓存
                    $this->clearThreadCach($thread_id);
                    $this->clearThreadCach($userStick->stick_thread_id);
                }
            }
        }

        $data=[
            "threadId" => $thread_id,
            "status" => $status==0?0:1
        ];

        $this->outPut(ResponseCode::SUCCESS,$status==0?'取消置顶成功':"置顶成功", $data);
    }

    private function clearThreadCach($threadId){
        DzqCache::delKey(CacheKey::LIST_THREADS_V3_COMPLEX);
    }
}
