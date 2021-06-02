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

namespace App\Api\Controller\AdminV3;

use App\Common\CacheKey;
use App\Models\AdminActionLog;
use App\Models\Category;
use App\Models\Post;
use App\Models\UserActionLogs;
use App\Traits\ThreadNoticesTrait;
use App\Traits\PostNoticesTrait;
use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use App\Models\Thread;
use Carbon\Carbon;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;;

class ManageSubmitReview extends DzqController
{

    use ThreadNoticesTrait;
    use PostNoticesTrait;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if (!$this->user->isAdmin()) {
            throw new PermissionDeniedException('没有权限');
        }
        return true;
    }

    public function main()
    {
        $user = $this->user;
        if (!$user->isAdmin()) {
            $this->outPut(ResponseCode::UNAUTHORIZED,'');
        }

        $data = $this->inPut('data');
        $type = $this->inPut('type'); //1主题 2评论
        if (empty($data) || !is_array($data) || empty($type)) {
            $this->outPut(ResponseCode::INVALID_PARAMETER,'');
        }

        $logArr = [];

        switch ($type) {
            case 1:
                $logArr = $this->threads();
                break;
            case 2:
                $logArr = $this->posts();
                break;
        }

        if (!empty($logArr)) {
            AdminActionLog::insert($logArr);

            $this->outPut(ResponseCode::SUCCESS);
        }

        $this->outPut(ResponseCode::INVALID_PARAMETER);
    }

    public function threads()
    {

        $user = $this->user;
        $data = $this->inPut('data');
        $ids = array_column($data,'id');
        $arr = array_column($data,null,'id');

        $logArr = [];
        $thread = Thread::query()->whereIn('id',$ids)->get();

        foreach ($thread as  $k => $v) {

            if($v->title == '' || empty($v->title)) {
                $threadTitle = '，其ID为'. $v->id;
            }else{
                $threadTitle = '【'. $v->title .'】';
            }
            //审核主题
            if (empty($v->deleted_at) && in_array($arr[$v->id]['isApproved'], [1, 2]) && $v->is_approved != $arr[$v->id]['isApproved']) {

                if ($arr[$v->id]['isApproved'] == 1) {
                    $action_desc = $threadTitle.',通过审核';
                    //统计分类主题数+1
                    Category::query()->where('id',$v->thread_id)->increment('thread_count');
                } else {
                    $action_desc = $threadTitle.',被忽略';
                }

                $v->is_approved = $arr[$v->id]['isApproved'];
                $v->save();

                $logArr[] = $this->logs('用户主题帖'. $action_desc);
                //删除主题
            }else if (in_array($arr[$v->id]['isDeleted'],[true, false])) {

                if ($arr[$v->id]['isDeleted'] == true) {
                    //软删除
                    if (empty($v->deleted_at)) {

                        $v->deleted_user_id = $user->id;
                        $v->deleted_at = Carbon::now();
                        $v->save();

                        // 通知
                        $this->threadNotices($v, $user, 'isDeleted', $arr[$v->id]['message'] ?? '');

                        // 日志
                        UserActionLogs::writeLog($user, $v, 'hide', $arr[$v->id]['message'] ?? '');

                        //统计分类主题数-1
                        Category::query()->where('id',$v->category_id)->decrement('thread_count');

                        $logArr[] = $this->logs('软删除用户主题帖'. $threadTitle);

                        //真删除
                    } else if (!empty($v->deleted_at)) {

                        $deleteThreads[] = $v->id;

                        $logArr[] = $this->logs('真删除用户主题帖'. $threadTitle);

                    }

                }
                //还原被删除的主题
                if (!empty($v->deleted_at) && $arr[$v->id]['isDeleted'] == false) {

                    $v->deleted_user_id = null;
                    $v->deleted_at = null;
                    $v->save();

                    // 日志
                    UserActionLogs::writeLog($user, $v, 'restore', $arr[$v->id]['message'] ?? '');

                    //统计分类主题数+1
                    Category::query()->where('id',$v->thread_id)->increment('thread_count');

                    $logArr[] = $this->logs('还原用户主题帖'. $threadTitle);
                }
            }
        }

        //处理真删除
        if (isset($deleteThreads)) {
            Thread::query()->whereIn('id',$deleteThreads)->delete();
        }

        CacheKey::delListCache();
        return $logArr;
    }

    public function posts()
    {

        $user = $this->user;
        $data = $this->inPut('data');
        $ids = array_column($data,'id');
        $arr = array_column($data,null,'id');

        $Post = Post::query()->whereIn('id',$ids)->get();

        $logArr = [];
        foreach( $Post as $k => $v ){

            if($v->content == '' || empty($v->content)) {
                $threadContent = '，其ID为'. $v->id;
            }else{
                $threadContent = '【'. $v->content .'】';
            }
            //审核回复
            if (empty($v->deleted_at) && in_array($arr[$v->id]['isApproved'], [1, 2]) && $v->is_approved != $arr[$v->id]['isApproved']) {

                if ($arr[$v->id]['isApproved']==1) {
                    $action_desc = $threadContent.',通过审核';
                    //统计帖子评论数+1
                    Thread::query()->where('id',$v->thread_id)->increment('post_count');
                } else {
                    $action_desc = $threadContent.',被忽略';
                }

                $v->is_approved = $arr[$v->id]['isApproved'];
                $v->save();

                $logArr[] = $this->logs('用户回复评论'. $action_desc);

                //删除回复
            } else if (in_array($arr[$v->id]['isDeleted'],[true, false])) {

                if ($arr[$v->id]['isDeleted'] == true) {

                    if (empty($v->deleted_at)) {

                        $v->deleted_user_id = $user->id;
                        $v->deleted_at = Carbon::now();
                        $v->save();

                        // 通知
                        $this->postNotices($v, $user, 'isDeleted', $arr[$v->id]['message'] ?? '');

                        // 日志
                        UserActionLogs::writeLog($user, $v, 'hide', $arr[$v->id]['message'] ?? '');

                        //统计帖子评论数-1
                        Thread::query()->where('id',$v->thread_id)->decrement('post_count');

                        $logArr[] = $this->logs('软删除用户回复评论'. $threadContent);
                        //真删除
                    } else if (!empty($v->deleted_at)) {

                        $deletePosts[] = $v->id;

                        $logArr[] = $this->logs('真删除用户回复评论'. $threadContent);

                    }

                }
                //还原被删除回复
                if ( !empty($v->deleted_at) && $arr[$v->id]['isDeleted'] == false) {

                    $v->deleted_user_id = null;
                    $v->deleted_at = null;
                    $v->save();

                    // 日志
                    UserActionLogs::writeLog($user, $v, 'restore', $arr[$v->id]['message'] ?? '');

                    //统计帖子评论数+1
                    Thread::query()->where('id',$v->thread_id)->increment('post_count');

                    $logArr[] = $this->logs('还原用户回复评论'. $threadContent);

                }
            }
        }

        //处理真删除
        if (isset($deletePosts)) {
            Post::query()->whereIn('id',$deletePosts)->delete();
        }

        return $logArr;
    }

    public function logs($actionDesc){
        return [
            'user_id' => $this->user->id,
            'action_desc' => '还原用户回复评论'. $actionDesc,
            'ip' => ip($this->request->getServerParams()),
            'created_at' => Carbon::now()
        ];
    }

}
