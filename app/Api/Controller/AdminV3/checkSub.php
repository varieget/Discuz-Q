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

use App\Common\ResponseCode;
use App\Models\Post;
use App\Repositories\UserRepository;
use App\Models\Thread;
use Carbon\Carbon;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Illuminate\Support\Str;
use App\Models\AdminActionLog;


class CheckSub extends DzqController
{

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
        $type = $this->inPut('type');
        if (empty($data) || !is_array($data) || empty($type)) {
            $this->outPut(ResponseCode::INVALID_PARAMETER,'');
        }

        $logArr = [];
        switch ($type) {
            case 1:
                $logArr = $this->threads($user, $data);
                break;
            case 2:
                $logArr = $this->posts($user, $data);
                break;
        }

        if (!empty($logArr)) {
            AdminActionLog::insert($logArr);
            $this->outPut(ResponseCode::SUCCESS,'', $logArr);
        }

        $this->outPut(ResponseCode::INVALID_PARAMETER,'', $logArr);
    }

    public function threads($user, $data){

        $ids = array_column($data,'id');
        $arr = array_column($data,null,'id');
        $serverParams = $this->request->getServerParams();
        $ip = ip($serverParams);

        $thread = Thread::query()->whereIn('id',$ids)
            ->get(['id','is_approved','title','deleted_at']);

        $logArr = [];
        foreach( $thread as $k => $v ){

            if($v->title == '' || empty($v->title)) {
                $threadTitle = '，其ID为'. $v->id;
            }else{
                $threadTitle = '【'. $v->title .'】';
            }

            if (empty($v->deleted_at) && ($arr[$v->id]['isApproved'] == 1 || $arr[$v->id]['isApproved'] == 2)) {

                if ($arr[$v->id]['isApproved']==1) {
                    $action_desc = $threadTitle.',通过审核';
                } else {
                    $action_desc = $threadTitle.',被忽略';
                }

                $v->is_approved = $arr[$v->id]['isApproved'];
                $v->save();

                $logArr[] = [
                    'user_id' => $user->id,
                    'action_desc' =>'用户主题帖'. $action_desc,
                    'ip' => $ip,
                    'created_at' => Carbon::now()
                ];

            } else if (is_bool($arr[$v->id]['isDeleted'])) {

                if (empty($v->deleted_at) && $arr[$v->id]['isDeleted'] == true) {

                    $action_desc = '批量删除用户主题帖'. $threadTitle;

                    $v->is_approved = $arr[$v->id]['isApproved'];
                    $v->deleted_user_id = $user->id;
                    $v->deleted_at = Carbon::now();
                    $v->save();

                    $logArr[] = [
                        'user_id' => $user->id,
                        'action_desc' => $action_desc,
                        'ip' => $ip,
                        'created_at' => Carbon::now()
                    ];
                }

                if (empty($v->deleted_at) && $arr[$v->id]['isDeleted'] == false) {

                    $action_desc = '批量还原用户主题帖'. $threadTitle;

                    $v->is_approved = $arr[$v->id]['isApproved'];
                    $v->deleted_user_id = null;
                    $v->deleted_at = null;
                    $v->save();

                    $logArr[] = [
                        'user_id' => $user->id,
                        'action_desc' => $action_desc,
                        'ip' => $ip,
                        'created_at' => Carbon::now()
                    ];
                }
            }
        }

        return $logArr;
    }

    public function posts($user, $data){

        $ids = array_column($data,'id');
        $arr = array_column($data,null,'id');
        $serverParams = $this->request->getServerParams();
        $ip = ip($serverParams);

        $Post = Post::query()->whereIn('id',$ids)
            ->get(['id','is_approved','content','deleted_at']);

        $logArr = [];
        foreach( $Post as $k => $v ){

            if($v->content == '' || empty($v->content)) {
                $threadContent = '，其ID为'. $v->id;
            }else{
                $threadContent = '【'. $v->content .'】';
            }

            if (empty($v->deleted_at) && ($arr[$v->id]['isApproved'] == 1 || $arr[$v->id]['isApproved'] == 2)) {

                if ($arr[$v->id]['isApproved']==1) {
                    $action_desc = $threadContent.',通过审核';
                } else {
                    $action_desc = $threadContent.',被忽略';
                }

                $v->is_approved = $arr[$v->id]['isApproved'];
                $v->save();

                $logArr[] = [
                    'user_id' => $user->id,
                    'action_desc' =>'用户回复评论'. $action_desc,
                    'ip' => $ip,
                    'created_at' => Carbon::now()
                ];

            } else if (is_bool($arr[$v->id]['isDeleted'])) {

                if (empty($v->deleted_at)  &&  $arr[$v->id]['isDeleted'] == true) {

                    $action_desc = '批量删除用户回复评论'. $threadContent;

                    $v->is_approved = $arr[$v->id]['isApproved'];
                    $v->deleted_user_id = $user->id;
                    $v->deleted_at = Carbon::now();
                    $v->save();

                    $logArr[] = [
                        'user_id' => $user->id,
                        'action_desc' => $action_desc,
                        'ip' => $ip,
                        'created_at' => Carbon::now()
                    ];

                }
                if ( !empty($v->deleted_at) && $arr[$v->id]['isDeleted'] == false) {

                    $action_desc = '批量还原用户回复评论'. $threadContent;

                    $v->is_approved = $arr[$v->id]['isApproved'];
                    $v->deleted_user_id = null;
                    $v->deleted_at = null;
                    $v->save();


                    $logArr[] = [
                        'user_id' => $user->id,
                        'action_desc' => $action_desc,
                        'ip' => $ip,
                        'created_at' => Carbon::now()
                    ];

                }
            }
        }

        return $logArr;
    }

}
