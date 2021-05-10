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
use App\Models\Group;
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
use App\Modules\ThreadTom\TomConfig;

trait ThreadListTrait
{
    private function getFullThreadData($threads)
    {
        $userIds = array_unique(array_column($threads, 'user_id'));
        $groupUsers = $this->getGroupUserInfo($userIds);
        $users = $this->extractCacheData(CacheKey::LIST_THREADS_V3_USERS, $userIds, function ($userIds) {
            $users = User::instance()->getUsers($userIds);
            $users = array_column($users, null, 'id');
            return $users;
        });
        $threadIds = array_column($threads, 'id');
        $posts = $this->extractCacheData(CacheKey::LIST_THREADS_V3_POSTS, $threadIds, function ($threadIds) {
            $posts = Post::instance()->getPosts($threadIds);
            $posts = array_column($posts, null, 'thread_id');
            return $posts;
        });
        $toms = $this->extractCacheData(CacheKey::LIST_THREADS_V3_TOMS, $threadIds, function ($threadIds) {
            $toms = ThreadTom::query()->whereIn('thread_id', $threadIds)->where('status', ThreadTom::STATUS_ACTIVE)->get()->toArray();
            $toms = $this->arrayColumnMulti($toms, 'thread_id');
            return $toms;
        });
        $tags = $this->extractCacheData(CacheKey::LIST_THREADS_V3_TAGS, $threadIds, function ($threadIds) {
            $tags = [];
            ThreadTag::query()->whereIn('thread_id', $threadIds)->get()->each(function ($item) use (&$tags) {
                $tags[$item['thread_id']][] = $item->toArray();
            });
            return $tags;
        });
        $inPutToms = $this->buildIPutToms($toms, $attachmentIds, $threadVideoIds);
        $result = [];
        $linkString = '';
        foreach ($threads as $thread) {
            $threadId = $thread['id'];
            $userId = $thread['user_id'];
            $user = empty($users[$userId]) ? false : $users[$userId];
            $groupUser = empty($groupUsers[$userId]) ? false : $groupUsers[$userId];
            $post = empty($posts[$threadId]) ? false : $posts[$threadId];
            $tomInput = empty($inPutToms[$threadId]) ? false : $inPutToms[$threadId];
            $threadTags = [];
            isset($tags[$threadId]) && $threadTags = $tags[$threadId];
            $linkString .= ($thread['title'] . $post['content']);
            $result[] = $this->packThreadDetail($user, $groupUser, $thread, $post, $tomInput, false, $threadTags);
        }
        $searchKeys = Thread::instance()->getSearchString($linkString);
        $sReplaces = $this->extractCacheData(CacheKey::LIST_THREADS_V3_SEARCH_REPLACE, $searchKeys);
        if ($sReplaces === false) {
            $sReplaces = Thread::instance()->getReplaceStringV3($linkString);
            $this->appendCacheData(CacheKey::LIST_THREADS_V3_SEARCH_REPLACE, $sReplaces);
        }
        $sReplaces = [];
        $searches = array_keys($sReplaces);
        $replaces = array_values($sReplaces);
        foreach ($result as &$item) {
            $item['title'] = str_replace($searches, $replaces, $item['title']);
            $item['content']['text'] = str_replace($searches, $replaces, $item['content']['text']);
        }
        return $result;
    }

    private function getGroupUserInfo($userIds)
    {
        $groups = array_column(Group::getGroups(), null, 'id');
        $groupUsers = $this->extractCacheData(CacheKey::LIST_THREADS_V3_GROUP_USER, $userIds, function ($userIds) {
            $groupUsers = GroupUser::query()->whereIn('user_id', $userIds)->get()->toArray();
            $groupUsers = array_column($groupUsers, null, 'user_id');
            return $groupUsers;
        });
        foreach ($groupUsers as &$groupUser) {
            $groupUser['groups'] = $groups[$groupUser['group_id']];
        }
        return $groupUsers;
    }

    private function arrayColumnMulti($array, $field)
    {
        $p = [];
        foreach ($array as $item) {
            $p[$item[$field]][] = $item;
        }
        return $p;
    }

    private function extractCacheData($cacheKey, $extractIds, callable $callback = null)
    {
        $cacheData = app('cache')->get($cacheKey);
        $ret = [];
        if (!empty($extractIds)) {
            $ret = [];
            if ($cacheData) {
                foreach ($extractIds as $extractId) {
                    if (array_key_exists($extractId, $cacheData)) {
                        if (!empty($cacheData[$extractId])) {
                            $ret[$extractId] = $cacheData[$extractId];
                        }
                    } else {
                        $ret = false;
                    }
                }
            }
        }
        if ($ret === false && !empty($callback)) {
            $ret = $callback($extractIds);
            $this->appendCacheData($cacheKey, $ret);
        }
        return $ret;
    }


    private function appendCacheData($cacheKey, $appendData)
    {
        $cacheData = app('cache')->get($cacheKey);
        if (!$cacheData) $cacheData = [];
        foreach ($appendData as $key => $value) {
            $cacheData[$key] = $value;
        }
        return app('cache')->put($cacheKey, $cacheData);
    }

    /**
     * @desc 未查询的数据添加默认空值
     * @param $ids
     * @param $array
     * @param null $value
     * @return mixed
     */
    private function appendDefaultEmpty($ids, &$array, $value = null)
    {
        foreach ($ids as $id) {
            if (!isset($array[$id])) {
                $array[$id] = $value;
            }
        }
        return $array;
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
        $loginUserId = $this->user->id;
        $threads = $this->resetThreads($threadsList);
        $threadIds = array_column($threads, 'id');
        $posts = $this->cachePosts($threadIds);
        $postIds = array_column($posts, 'id');
        $userIds = array_unique(array_column($threads, 'user_id'));
        $this->cacheThreads($threads);
        $this->cacheUsers($userIds);
        $this->cacheGroupUser($userIds);
        $this->cacheTags($threadIds);
        $attachmentIds = [];
        $threadVideoIds = [];
        $toms = $this->cacheToms($threadIds);
        $this->buildIPutToms($toms, $attachmentIds, $threadVideoIds);
        $this->cacheAttachment($attachmentIds);
        $this->cacheVideo($threadVideoIds);
        $this->cacheUserOrders($loginUserId, $threadIds);
        $this->cachePostUsers($threadIds, $postIds, $posts);
        $this->cachePostLikedAndFavor($loginUserId, $threadIds, $postIds);
        $posts = array_column($posts, null, 'thread_id');
        $this->cacheSearchReplace($threads, $posts);
    }

    private function resetThreads($threadsList)
    {
        $threads = [];
        foreach ($threadsList as $listItems) {
            $pageData = $listItems['pageData'];
            foreach ($pageData as $thread) {
                $threads[] = $thread;
            }
        }
        return $threads;
    }

    private function groupKey()
    {
        $groups = $this->user->groups->toArray();
        $groupIds = array_column($groups, 'id');
        return md5(serialize($groupIds));
    }

    private function buildIPutToms($tomData, &$attachmentIds, &$threadVideoIds)
    {
        $inPutToms = [];
        foreach ($tomData as $threadId => $toms) {
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
        }
        $attachmentIds = array_values(array_unique($attachmentIds));
        $threadVideoIds = array_values(array_unique($threadVideoIds));
        return $inPutToms;
    }

    private function cacheUsers($userIds)
    {
        $users = User::instance()->getUsers($userIds);
        $users = array_column($users, null, 'id');
        app('cache')->put(CacheKey::LIST_THREADS_V3_USERS, $users);
        return $users;
    }

    private function cacheGroupUser($userIds)
    {
        $groupUsers = GroupUser::query()->whereIn('user_id', $userIds)->get()->toArray();
        $groupUsers = array_column($groupUsers, null, 'user_id');
        app('cache')->put(CacheKey::LIST_THREADS_V3_GROUP_USER, $groupUsers);
        return $groupUsers;
    }

    private function cacheTags($threadIds)
    {
        $tags = [];
        ThreadTag::query()->whereIn('thread_id', $threadIds)->get()->each(function ($item) use (&$tags) {
            $tags[$item['thread_id']][] = $item->toArray();
        });
        $tags = $this->appendDefaultEmpty($threadIds, $tags, []);
        app('cache')->put(CacheKey::LIST_THREADS_V3_TAGS, $tags);
        return $tags;
    }

    private function cacheThreads($threads)
    {
        $threads = array_column($threads, null, 'id');
        app('cache')->put(CacheKey::LIST_THREADS_V3_THREADS, $threads);
        return $threads;
    }

    private function cachePosts($threadIds)
    {
        $posts = Post::instance()->getPosts($threadIds);
        $posts = array_column($posts, null, 'thread_id');
        app('cache')->put(CacheKey::LIST_THREADS_V3_POSTS, $posts);
        return $posts;
    }

    private function cacheToms($threadIds)
    {
        $toms = ThreadTom::query()->whereIn('thread_id', $threadIds)->where('status', ThreadTom::STATUS_ACTIVE)->get()->toArray();
        $toms = $this->arrayColumnMulti($toms, 'thread_id');
        $toms = $this->appendDefaultEmpty($threadIds, $toms, []);
        app('cache')->put(CacheKey::LIST_THREADS_V3_TOMS, $toms);
        return $toms;
    }

    private function cacheSearchReplace($threads, $posts)
    {
        $linkString = '';
        foreach ($threads as $thread) {
            $threadId = $thread['id'];
            $post = $posts[$threadId] ?? '';
            $linkString .= ($thread['title'] . $post['content'] ?? '');
        }
        $sReplaces = Thread::instance()->getReplaceStringV3($linkString);
        app('cache')->put(CacheKey::LIST_THREADS_V3_SEARCH_REPLACE, $sReplaces);
        return $sReplaces;
    }

    private function cacheAttachment($attachmentIds)
    {
        $attachments = Attachment::query()->whereIn('id', $attachmentIds)->get()->pluck(null, 'id');
        $attachments = $this->appendDefaultEmpty($attachmentIds, $attachments, null);
        app('cache')->put(CacheKey::LIST_THREADS_V3_ATTACHMENT, $attachments);
        return $attachments;
    }

    private function cacheVideo($threadVideoIds)
    {
        $threadVideos = ThreadVideo::query()->whereIn('id', $threadVideoIds)->where('status', ThreadVideo::VIDEO_STATUS_SUCCESS)->get()->pluck(null, 'id')->toArray();
        $threadVideos = $this->appendDefaultEmpty($threadVideoIds, $threadVideos, null);
        app('cache')->put(CacheKey::LIST_THREADS_V3_VIDEO, $threadVideos);
        return $threadVideos;
    }

    private function cacheUserOrders($userId, $threadIds)
    {
        $orders = Order::query()
            ->where([
                'user_id' => $userId,
                'status' => Order::ORDER_STATUS_PAID
            ])->whereIn('type', [Order::ORDER_TYPE_THREAD, Order::ORDER_TYPE_ATTACHMENT])
            ->whereIn('thread_id', $threadIds)->get()->pluck(null, 'thread_id')->toArray();
        $orders = $this->appendDefaultEmpty($threadIds, $orders, null);
        $userOrders = app('cache')->get(CacheKey::LIST_THREADS_V3_USER_ORDERS);
        if ($userOrders) {
            $userOrders[$userId] = $orders;
        } else {
            $userOrders = [$userId => $orders];
        }
        app('cache')->put(CacheKey::LIST_THREADS_V3_USER_ORDERS, $userOrders);
        return $userOrders;
    }

    private function cachePostUsers($threadIds, $postIds, $posts)
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
        $likedUsers = $this->appendDefaultEmpty($threadIds, $likedUsers, []);
        app('cache')->put(CacheKey::LIST_THREADS_V3_POST_USERS, $likedUsers);
        return $likedUsers;
    }

    //点赞收藏
    private function cachePostLikedAndFavor($userId, $threadIds, $postIds)
    {
        $postUsers = PostUser::query()->where('user_id', $userId)
            ->whereIn('post_id', $postIds)
            ->get()
            ->pluck(null, 'post_id')->toArray();

        $postUsers = $this->appendDefaultEmpty($postIds, $postUsers, null);

        //是否点赞
        $postUsersLike = app('cache')->get(CacheKey::LIST_THREADS_V3_POST_LIKED);
        if ($postUsersLike) {
            $postUsersLike[$userId] = $postUsers;
        } else {
            $postUsersLike = [$userId => $postUsers];
        }

        $favorite = ThreadUser::query()->whereIn('thread_id', $threadIds)->where('user_id', $this->user->id)->get()
            ->pluck(null, 'thread_id')->toArray();
        $favorite = $this->appendDefaultEmpty($threadIds, $favorite, null);
        $postFavor = app('cache')->get(CacheKey::LIST_THREADS_V3_POST_FAVOR);
        if ($postFavor) {
            $postFavor[$userId] = $favorite;
        } else {
            $postFavor = [$userId => $favorite];
        }
        app('cache')->put(CacheKey::LIST_THREADS_V3_POST_LIKED, $postUsersLike);//点赞
        app('cache')->put(CacheKey::LIST_THREADS_V3_POST_FAVOR, $postFavor);//收藏
        return [$postUsersLike, $postFavor];
    }


}
