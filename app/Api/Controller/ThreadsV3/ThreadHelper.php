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
use App\Models\Order;
use App\Models\PostUser;
use App\Models\Thread;
use App\Models\User;
use Discuz\Base\DzqCache;

class ThreadHelper
{
    public static function getThreadLikedDetail($threadIds, $postIds, $posts, $isArray = true)
    {
        if (!$isArray) {
            $threadIds = [$threadIds];
            $postIds = [$postIds];
            $posts = [$posts];
        }
        //查询点赞人数
        $postIdThreadId = array_column($posts, 'thread_id', 'id');
        $v1 = PostUser::query()
            ->select(['a.post_id', 'a.user_id', 'a.created_at'])
            ->from('post_user as a')
            ->whereIn('post_id', $postIds)
            ->where(function ($query) {
                $query->selectRaw('count(0)')
                    ->from('post_user as b')
                    ->where('b.post_id', 'a.post_id')
                    ->where('b.created_at', '>', 'a.created_at');
            }, '<', 2)
            ->orderByDesc('a.post_id')
            ->get()->each(function (&$item) use ($postIdThreadId) {
                $item['thread_id'] = $postIdThreadId[$item['post_id']] ?? null;
                $item['type'] = 1;
            })->toArray();

        $v2 = Order::query()
            ->select(['a.thread_id', 'a.user_id', 'a.created_at'])
            ->from('orders as a')
            ->whereIn('thread_id', $threadIds)
            ->whereIn('a.type', [Order::ORDER_TYPE_REWARD, Order::ORDER_TYPE_THREAD, Order::ORDER_TYPE_ATTACHMENT])
            ->where('status', Order::ORDER_STATUS_PAID)
            ->where(function ($query) {
                $query->selectRaw('count(0)')
                    ->from('orders as b')
                    ->where('b.thread_id', 'a.thread_id')
                    ->where('b.created_at', '>', 'a.created_at');
            }, '<', 2)
            ->orderByDesc('a.thread_id')
            ->get()->toArray();

        $userIds = array_unique(array_merge(array_column($v1, 'user_id'), array_column($v2, 'user_id')));

        $users = User::query()->whereIn('id', $userIds)->get()->pluck(null, 'id');
        $mLikedUsers = array_merge($v1, $v2);
        usort($mLikedUsers, function ($a, $b) {
            return strtotime($a['created_at']) < strtotime($b['created_at']);
        });
        $likedUsersInfo = [];
        $maxDisplay = 3;
        foreach ($mLikedUsers as $item) {
            $threadId = $item['thread_id'];
            if (empty($likedUsersInfo[$threadId]) || count($likedUsersInfo[$threadId]) < $maxDisplay) {
                $user = $users[$item['user_id']] ?? null;
                $likedUsersInfo[$item['thread_id']][] = [
                    'userId' => $item['user_id'],
                    'avatar' => $user->avatar,
                    'userName' => !empty($user->nickname) ? $user->nickname : $user->username,
                    'type'  =>  !empty($item['type']) ? 1 : 2,
                    'createdAt'    => strtotime($item['created_at'])
                ];
            }
        }
        $likedUsersInfo = self::appendDefaultEmpty($threadIds, $likedUsersInfo, []);
        return $likedUsersInfo;
    }

    public static function getThreadSearchReplace($concatString)
    {
        $searchIds = Thread::instance()->getSearchString($concatString);
        $sReplaces = DzqCache::hMGet(CacheKey::LIST_THREADS_V3_SEARCH_REPLACE, $searchIds, function () use ($concatString) {
            return Thread::instance()->getReplaceStringV3($concatString);
        });
        $searches = array_keys($sReplaces);
        $replaces = array_values($sReplaces);
        return [$searches, $replaces];
    }

    private static function appendDefaultEmpty($ids, &$array, $value = null)
    {
        foreach ($ids as $id) {
            if (!isset($array[$id])) {
                $array[$id] = $value;
            }
        }
        return $array;
    }
}
