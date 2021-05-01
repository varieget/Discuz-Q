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
use App\Models\Attachment;
use App\Models\Category;
use App\Models\GroupUser;
use App\Models\Order;
use App\Models\Post;
use App\Models\PostUser;
use App\Models\Thread;
use App\Models\ThreadUser;
use App\Models\ThreadTag;
use App\Models\ThreadTom;
use App\Models\ThreadVideo;
use App\Models\User;
use App\Modules\ThreadTom\PreQuery;
use App\Modules\ThreadTom\TomConfig;

trait ThreadListTrait
{
    private function getFullThreadData($threadCollection)
    {
        $threads = $threadCollection->toArray();
        $userIds = array_unique(array_column($threads, 'user_id'));
        $groups = GroupUser::instance()->getGroupInfo($userIds);
        $groups = array_column($groups, null, 'user_id');
        $users = User::instance()->getUsers($userIds);
        $users = array_column($users, null, 'id');
        $threadIds = array_column($threads, 'id');
        $posts = Post::instance()->getPosts($threadIds);
        $postIds = array_column($posts, 'id');
        $postsByThreadId = array_column($posts, null, 'thread_id');
        $toms = ThreadTom::query()->whereIn('thread_id', $threadIds)->where('status', ThreadTom::STATUS_ACTIVE)->get();
        $tags = [];
        ThreadTag::query()->whereIn('thread_id', $threadIds)->get()->each(function ($item) use (&$tags) {
            $tags[$item['thread_id']][] = $item->toArray();
        });
        $inPutToms = $this->preQuery($toms, $threadIds, $postIds, $threads, $posts);
        $result = [];
        $linkString = '';

        foreach ($threads as $thread) {
            $threadId = $thread['id'];
            $userId = $thread['user_id'];
            $user = empty($users[$userId]) ? false : $users[$userId];
            $group = empty($groups[$userId]) ? false : $groups[$userId];
            $post = empty($postsByThreadId[$threadId]) ? false : $postsByThreadId[$threadId];
            $tomInput = empty($inPutToms[$threadId]) ? false : $inPutToms[$threadId];
            $threadTags = [];
            isset($tags[$threadId]) && $threadTags = $tags[$threadId];
            $linkString .= ($thread['title'] . $post['content']);
            $result[] = $this->packThreadDetail($user, $group, $thread, $post, $tomInput, false, $threadTags);
        }
        list($search, $replace) = Thread::instance()->getReplaceString($linkString);
        foreach ($result as &$item) {
            $item['title'] = str_replace($search, $replace, $item['title']);
            $item['content']['text'] = str_replace($search, $replace, $item['content']['text']);
        }
        return $result;
    }


    private function initDzqThreadsData($threads)
    {
        $threads = $threads->toArray();
        $cache = app('cache');
        $groupKey = $this->groupKey();
        $cacheKey = CacheKey::LIST_THREADS_V3 . $groupKey;
        $filter = $this->inPut('filter');
        $filterKey = md5(serialize($filter));
        $data = $cache->get($cacheKey);
        if ($data) {
            $data[$filterKey] = $threads;
        } else {
            $data = [$filterKey => $threads];
        }
        $cache->put($cacheKey, $data);
        $this->initDzqUnitData($threads);
    }

    private function initDzqUnitData($threadsList)
    {
        $wholeThreads = [];
        foreach ($threadsList as $threads) {
            $pageData = $threads['pageData'];
            foreach ($pageData as $thread) {
                $wholeThreads[] = $thread;
            }
        }
        $userIds = array_unique(array_column($wholeThreads, 'user_id'));
        $groups = GroupUser::instance()->getGroupInfo($wholeThreads);
        $groups = array_column($groups, null, 'user_id');
        $users = User::instance()->getUsers($userIds);
        $users = array_column($users, null, 'id');

        app('cache')->put(CacheKey::LIST_THREADS_V3_USERS, $users);

        $threadIds = array_column($wholeThreads, 'id');
        $wholePosts = Post::instance()->getPosts($threadIds);


        $postIds = array_column($wholePosts, 'id');
        $postsByThreadId = array_column($wholePosts, null, 'thread_id');
        $toms = ThreadTom::query()->whereIn('thread_id', $threadIds)->where('status', ThreadTom::STATUS_ACTIVE)->get();
        $tags = [];
        ThreadTag::query()->whereIn('thread_id', $threadIds)->get()->each(function ($item) use (&$tags) {
            $tags[$item['thread_id']][] = $item->toArray();
        });
        app('cache')->put(CacheKey::LIST_THREADS_V3_TAGS, $tags);
        $inPutToms = $this->preQuery($toms, $threadIds, $postIds, $wholeThreads, $wholePosts);
        $result = [];
        $linkString = '';
        foreach ($wholeThreads as $thread) {
            $threadId = $thread['id'];
            $userId = $thread['user_id'];
            $user = empty($users[$userId]) ? false : $users[$userId];
            $group = empty($groups[$userId]) ? false : $groups[$userId];
            $post = empty($postsByThreadId[$threadId]) ? false : $postsByThreadId[$threadId];
            $tomInput = empty($inPutToms[$threadId]) ? false : $inPutToms[$threadId];
            $threadTags = [];
            isset($tags[$threadId]) && $threadTags = $tags[$threadId];
            $result[] = $this->packThreadDetail($user, $group, $thread, $post, $tomInput, false, $threadTags);
            $linkString .= ($thread['title'] . $post['content']);
        }
        list($search, $replace) = Thread::instance()->getReplaceString($linkString);
        app('cache')->put(CacheKey::LIST_THREADS_V3_SEARCH_REPLACE, [$search, $replace]);
        foreach ($result as &$item) {
            $item['title'] = str_replace($search, $replace, $item['title']);
            $item['content']['text'] = str_replace($search, $replace, $item['content']['text']);
        }
        return $result;
    }


    private function groupKey()
    {
        $groups = $this->user->groups->toArray();
        $groupIds = array_column($groups, 'id');
        return md5(serialize($groupIds));
    }

    /**
     * @desc 预加载列表页数据
     * @param $toms
     * @param $threadIds
     * @param $postIds
     * @param $threads
     * @param $posts
     * @return array
     */
//    private function preQuery($toms, $threadCollection, $threadIds, $postIds, $threads, $posts)
    private function preQuery($toms, $threadIds, $postIds, $threads, $posts)
    {
        app('cache')->put(CacheKey::LIST_THREADS_V3_THREADS, array_column($threads, null, 'id'));
        app('cache')->put(CacheKey::LIST_THREADS_V3_POSTS, array_column($posts, null, 'id'));
        $attachmentIds = [];
        $threadVideoIds = [];
        $userId = $this->user->id;
//        $inPutToms = [];
//        foreach ($toms as $tom) {
//            $value = json_decode($tom['value'], true);
//            switch ($tom['tom_type']) {
//                case TomConfig::TOM_IMAGE:
//                    isset($value['imageIds']) && $attachmentIds = array_merge($attachmentIds, $value['imageIds']);
//                    break;
//                case TomConfig::TOM_DOC:
//                    isset($value['docIds']) && $attachmentIds = array_merge($attachmentIds, $value['docIds']);
//                    break;
//                case TomConfig::TOM_VIDEO:
//                    isset($value['videoId']) && $threadVideoIds[] = $value['videoId'];
//                    break;
//                case TomConfig::TOM_AUDIO:
//                    isset($value['audioId']) && $threadVideoIds[] = $value['audioId'];
//                    break;
//            }
//            $inPutToms[$tom['thread_id']][$tom['key']] = $this->buildTomJson($tom['thread_id'], $tom['tom_type'], $this->SELECT_FUNC, $value);
//        }
        $inPutToms = $this->buildIPutToms($toms,$attachmentIds,$threadVideoIds);

        $attachments = Attachment::query()->whereIn('id', $attachmentIds)->get()->pluck(null, 'id');

        app('cache')->put(CacheKey::LIST_THREADS_V3_ATTACHMENT, $attachments);


        $threadVideos = ThreadVideo::query()->whereIn('id', $threadVideoIds)->where('status', ThreadVideo::VIDEO_STATUS_SUCCESS)->get()->pluck(null, 'id');

        app('cache')->put(CacheKey::LIST_THREADS_V3_VIDEO, $threadVideos);

        $this->likeAndFavor($userId, $postIds, $threadIds);
        $orders = Order::query()
            ->where([
                'user_id' => $userId,
                'status' => Order::ORDER_STATUS_PAID
            ])->whereIn('type', [Order::ORDER_TYPE_THREAD, Order::ORDER_TYPE_ATTACHMENT])
            ->whereIn('thread_id', $threadIds)->get()->pluck(null, 'thread_id');

        $userOrders = app('cache')->get(CacheKey::LIST_THREADS_V3_USER_ORDERS);
        if ($userOrders) {
            $userOrders[$userId] = $orders;
        } else {
            $userOrders = [$userId => $orders];
        }
        app('cache')->put(CacheKey::LIST_THREADS_V3_USER_ORDERS, $userOrders);


        $likedUsers = $this->getThreadLikedUsers($postIds, $threadIds, $posts);

        app('cache')->put(CacheKey::LIST_THREADS_V3_POST_USERS, $likedUsers);


//
//        $threads = array_column($threads, null, 'id');
//        $posts = array_column($posts, null, 'id');
//        app()->instance(PreQuery::THREAD_LIST_ATTACHMENTS, $attachments);
//        app()->instance(PreQuery::THREAD_LIST_VIDEO, $threadVideos);
//        app()->instance(PreQuery::THREAD_LIST, $threadList);
//        app()->instance(PreQuery::THREAD_LIST_FAVORITE, $favorite);
//        app()->instance(PreQuery::THREAD_LIST_ORDERS, $orders);
//        app()->instance(PreQuery::THREAD_LIST_LIKED, $postUsers);
//        app()->instance(PreQuery::THREAD_LIST_LIKED_USERS, $likedUsers);
//        app()->instance(PreQuery::THREAD_LIST_THREADS, $threads);
//        app()->instance(PreQuery::THREAD_LIST_POSTS, $posts);

        return $inPutToms;
    }

    private function buildIPutToms($toms,&$attachmentIds,&$threadVideoIds)
    {
        $inPutToms = [];
        foreach ($toms as $tom) {
            $value = json_decode($tom['value'], true);
            switch ($tom['tom_type']) {
                case TomConfig::TOM_IMAGE:
                    isset($value['imageIds']) && $attachmentIds = array_merge($attachmentIds, $value['imageIds']);
                    break;
                case TomConfig::TOM_DOC:
                    isset($value['docIds']) && $attachmentIds = array_merge($attachmentIds, $value['docIds']);
                    break;
                case TomConfig::TOM_VIDEO:
                    isset($value['videoId']) && $threadVideoIds[] = $value['videoId'];
                    break;
                case TomConfig::TOM_AUDIO:
                    isset($value['audioId']) && $threadVideoIds[] = $value['audioId'];
                    break;
            }
            $inPutToms[$tom['thread_id']][$tom['key']] = $this->buildTomJson($tom['thread_id'], $tom['tom_type'], $this->SELECT_FUNC, $value);
        }
        $attachmentIds = array_unique($attachmentIds);
        $threadVideoIds = array_unique($threadVideoIds);
        return $inPutToms;
    }

    //点赞收藏
    private function likeAndFavor($userId, $postIds, $threadIds)
    {
        $postUsers = PostUser::query()->where('user_id', $userId)
            ->whereIn('post_id', $postIds)
            ->get()->pluck(null, 'post_id');
        //是否点赞
        $postUsersLike = app('cache')->get(CacheKey::LIST_THREADS_V3_POST_LIKED);
        if ($postUsersLike) {
            $postUsersLike[$userId] = $postUsers;
        } else {
            $postUsersLike = [$userId => $postUsers];
        }
        app('cache')->put(CacheKey::LIST_THREADS_V3_POST_LIKED, $postUsersLike);

        $favorite = ThreadUser::query()->whereIn('thread_id', $threadIds)->where('user_id', $this->user->id)->get()->pluck(null, 'thread_id');
        $postFavor = app('cache')->get(CacheKey::LIST_THREADS_V3_POST_FAVOR);
        if ($postFavor) {
            $postFavor[$userId] = $favorite;
        } else {
            $postFavor = [$userId => $favorite];
        }
        app('cache')->put(CacheKey::LIST_THREADS_V3_POST_FAVOR, $postFavor);
    }

    private function getThreadLikedUsers($postIds, $threadIds, $posts)
    {
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
            })->toArray();

        $v2 = Order::query()
            ->select(['a.thread_id', 'a.user_id', 'a.created_at'])
            ->from('orders as a')
            ->whereIn('thread_id', $threadIds)
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
        $likedUsers = [];
        $maxDisplay = 2;
        foreach ($mLikedUsers as $item) {
            $threadId = $item['thread_id'];
            if (empty($likedUsers[$threadId]) || count($likedUsers[$threadId]) < $maxDisplay) {
                $user = $users[$item['user_id']] ?? null;
                $likedUsers[$item['thread_id']][] = [
                    'userId' => $item['user_id'],
                    'avatar' => $user->avatar,
                    'userName' => !empty($user->nickname) ? $user->nickname : $user->username
                ];
            }
        }
        return $likedUsers;
    }

}
