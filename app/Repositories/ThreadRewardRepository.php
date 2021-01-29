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

namespace App\Repositories;

use App\Models\Thread;
use App\Models\ThreadReward;
use App\Models\User;
use App\Models\Order;
use App\Models\Post;
use App\Api\Serializer\ThreadSerializer;
use App\Api\Serializer\UserSerializer;
use Discuz\Foundation\AbstractRepository;
use Illuminate\Support\Arr;
use App\Notifications\Messages\Wechat\ThreadRewardedWechatMessage;
use App\Notifications\Messages\Wechat\ThreadRewardedExpiredWechatMessage;
use App\Notifications\ThreadRewarded;
use Tobscure\JsonApi\Relationship;
use Discuz\Api\Serializer\AbstractSerializer;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\Database\Eloquent\Model;

class ThreadRewardRepository extends AbstractRepository
{
    /**
     * Find a thread by ID, optionally making sure it is visible to a
     * certain user, or throw an exception.
     *
     * @param int $id
     * @param User|null $actor
     * @return Thread
     */
    public function returnThreadRewardNotify($thread_id, $user_id, $rewards, $type)
    {
        $query = Thread::query();
        $query->where(['id' => $thread_id]);
        $thread = $query->first();

        $order = Order::query()->where(['thread_id' => $thread_id])->first();
        $actorUser = User::query()->where(['id' => $thread->user_id])->first();
        $user = User::query()->where(['id' => $user_id])->first();
        $orderArr = empty($order) ? array() : $order->toArray();

        if(!empty($thread)){
            if(!empty($thread->title)){
                $threadContent = $thread->title;
            }else{
                $post = Post::query()->where(['thread_id' => $thread_id, 'is_first' => 1])->first();
                $threadContent = $post->content;
            }
        }else{
            $threadContent = '悬赏帖已过期且已被删除，返回冻结金额';
        }


        $build = [
            'message' => $threadContent,
            'raw' => array_merge(Arr::only($orderArr, ['id', 'thread_id', 'type']), [
                'actor_username' => $actorUser->username,   // 发送人姓名
                'actual_amount' => $rewards,     // 获取作者实际金额
                'title' => $thread->title,
                'content' => $threadContent,
                'created_at' => (string)$thread->created_at
            ]),
        ];
        app('log')->info('ThreadRewardRepository.php文件'. __LINE__ . '行：给被采纳者用户准备悬赏信息用来通知。');
        $walletType = $type;
        // Tag 发送悬赏问答通知
        $user->notify(new ThreadRewarded(ThreadRewardedWechatMessage::class, $user, $order, $build, $walletType));
    }
}
