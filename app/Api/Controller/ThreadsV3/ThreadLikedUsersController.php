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

#帖子点赞打赏用户列表
use App\Common\ResponseCode;
use App\User\AvatarUploader;
use App\Models\Thread;
use App\Models\PostUser;
use App\Models\User;
use App\Models\Order;
use Discuz\Base\DzqController;

class ThreadLikedUsersController extends DzqController
{

    public $uploader;

    public function __construct(AvatarUploader $uploader)
    {
        $this->uploader = $uploader;
        // TODO: Implement __call() method.
    }

    public function main()
    {
        $data = [
            'postId' => $this->inPut('postId'),
            'threadId' => $this->inPut('threadId'),
            'perPage' => $this->inPut('perPage') ? $this->inPut('perPage') : 10,
            'page' => $this->inPut('page') ? $this->inPut('page') : 0,
            'type' => $this->inPut('type') ? $this->inPut('type') : 0
        ];

        $this->dzqValidate($data,[
            'postId' => 'required|int',
            'threadId' => 'required|int',
            'type' => 'required|integer|in:0,1,2'
        ]);

        $thread = Thread::query()->where('id',$data['threadId'])->first(['price']);

        $postUser = PostUser::query()
            ->where('post_id',$data['postId'])
            ->orderBy('created_at','desc')
            ->get(['user_id','created_at'])
            ->toArray();

        $isPaid = $thread['price'] > 0 ? Order::ORDER_TYPE_THREAD : Order::ORDER_TYPE_REWARD ;
        $order = Order::query()->where('thread_id',$data['threadId'])
            ->where('type',$isPaid)
            ->orderBy('created_at','desc')
            ->get(['user_id','created_at'])
            ->toArray();

        if (empty($data['type'])) {
            $postUserAndorder = array_merge($postUser,$order);
        } else if ($data['type'] == 1){
            $postUserAndorder = $postUser;
        } else {
            $postUserAndorder = $order;
        }

        $postUserIds = array_column($postUserAndorder,'user_id');
        $user = User::query()->whereIn('id',array_unique($postUserIds))->get(['id','username','avatar'])->toArray();
        $userArr = array_combine(array_column($user, 'id'), $user);
        $likeSort = $this->arraySort($postUserAndorder,'created_at','desc');
        foreach ($likeSort as $k=>$v) {
            $likeSort[$k]['passed_at'] = $this->format_date(strtotime($v['created_at']));
            $likeSort[$k]['username'] = $userArr[$v['user_id']]['username'];
            $likeSort[$k]['avatar'] = $userArr[$v['user_id']]['avatar'];
        }

        $retrue = [
            'allCount' => count($postUser)+count($order),
            'likeCount' => count($postUser),
            'rewardCount' => $thread['price'] > 0 ? 0 : count($order),
            'raidCount' => $thread['price'] > 0 ? count($order) : 0,
            'list'=>array_values(array_slice($likeSort, $data['page'], $data['perPage']))
        ];

        $this->outPut(ResponseCode::SUCCESS, '', $retrue);
        // TODO: Implement main() method.
    }

    public function arraySort($arr, $keys, $type = 'asc') {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v){
            $keysvalue[$k] = $v[$keys];
        }
        $type == 'asc' ? asort($keysvalue) : arsort($keysvalue);
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }

    public function format_date($time){
        $t=time()-$time;
        $f=array(
            '31536000'=>'年',
            '2592000'=>'个月',
            '604800'=>'星期',
            '86400'=>'天',
            '3600'=>'小时',
            '60'=>'分钟',
            '1'=>'秒'
        );
        foreach ($f as $k=>$v)    {
            if (0 !=$c=floor($t/(int)$k)) {
                return $c.$v;
            }
        }
        return $f;
    }
}
