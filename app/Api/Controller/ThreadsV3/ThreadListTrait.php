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
use App\Common\DzqCache;
use App\Models\Attachment;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\Order;
use App\Models\Post;
use App\Models\Thread;
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
        $users = DzqCache::extractCacheArrayData(CacheKey::LIST_THREADS_V3_USERS, $userIds, function ($userIds) {
            $users = User::instance()->getUsers($userIds);
            $users = array_column($users, null, 'id');
            return $users;
        });
        $threadIds = array_column($threads, 'id');
        $posts = DzqCache::extractCacheArrayData(CacheKey::LIST_THREADS_V3_POSTS, $threadIds, function ($threadIds) {
            $posts = Post::instance()->getPosts($threadIds);
            $posts = array_column($posts, null, 'thread_id');
            return $posts;
        });
        $toms = DzqCache::extractCacheArrayData(CacheKey::LIST_THREADS_V3_TOMS, $threadIds, function ($threadIds) {
            $toms = ThreadTom::query()->whereIn('thread_id', $threadIds)->where('status', ThreadTom::STATUS_ACTIVE)->get()->toArray();
            $toms = $this->arrayColumnMulti($toms, 'thread_id');
            return $toms;
        });
        $tags = DzqCache::extractCacheArrayData(CacheKey::LIST_THREADS_V3_TAGS, $threadIds, function ($threadIds) {
            $tags = [];
            ThreadTag::query()->whereIn('thread_id', $threadIds)->get()->each(function ($item) use (&$tags) {
                $tags[$item['thread_id']][] = $item->toArray();
            });
            return $tags;
        });
        $inPutToms = $this->buildIPutToms($toms);
        $result = [];
        $concatString = '';
        foreach ($threads as $thread) {
            $threadId = $thread['id'];
            $userId = $thread['user_id'];
            $user = empty($users[$userId]) ? false : $users[$userId];
            $groupUser = empty($groupUsers[$userId]) ? false : $groupUsers[$userId];
            $post = empty($posts[$threadId]) ? false : $posts[$threadId];
            $tomInput = empty($inPutToms[$threadId]) ? false : $inPutToms[$threadId];
            $threadTags = [];
            isset($tags[$threadId]) && $threadTags = $tags[$threadId];
            $concatString .= ($thread['title'] . $post['content']);
            $result[] = $this->packThreadDetail($user, $groupUser, $thread, $post, $tomInput, false, $threadTags);
        }
        list($searches, $replaces) = ThreadHelper::getThreadSearchReplace($concatString);
        foreach ($result as &$item) {
            $item['title'] = str_replace($searches, $replaces, $item['title']);
            $item['content']['text'] = str_replace($searches, $replaces, $item['content']['text']);
        }
        return $result;
    }

    private function getGroupUserInfo($userIds)
    {
        $groups = array_column(Group::getGroups(), null, 'id');
        $groupUsers = DzqCache::extractCacheArrayData(CacheKey::LIST_THREADS_V3_GROUP_USER, $userIds, function ($userIds) {
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


    /**
     * @desc 未查询到的数据添加默认空值
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

    private function initDzqThreadsData($cacheKey,$threads)
    {
        $cache = app('cache');
        $filter = $this->inPut('filter');
        $filterKey = md5(serialize($filter));
        $data = $cache->get($cacheKey);
        if ($data) {
            $data[$filterKey] = $threads;
        } else {
            $data = [$filterKey => $threads];
        }
        $cache->put($cacheKey, $data);
        $this->initDzqUnitData($this->user->id, $threads);
    }

    private function initDzqUnitData($loginUserId, $threadsList)
    {
        $threads = $this->getAllThreadsList($threadsList);
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
        $this->buildIPutToms($toms, $attachmentIds, $threadVideoIds, true);
        $this->cacheAttachment($attachmentIds);
        $this->cacheVideo($threadVideoIds);
        $this->cacheUserOrders($loginUserId, $threadIds);
        $this->cachePostUsers($threadIds, $postIds, $posts);
        $this->cachePostLikedAndFavor($loginUserId, $threadIds, $postIds);
        $posts = array_column($posts, null, 'thread_id');
        $this->cacheSearchReplace($threads, $posts);
    }

    private function getAllThreadsList($threadsByPage)
    {
        $threads = [];
        foreach ($threadsByPage as $listItems) {
            $pageData = $listItems['pageData'];
            foreach ($pageData as $thread) {
                $threads[] = $thread;
            }
        }
        return $threads;
    }

//    private function groupId()
//    {
//        $groups = $this->user->groups->toArray();
//        $groupIds = array_column($groups, 'id');
//        return $groupIds[0] ?? 0;
//    }

    private function buildIPutToms($tomData, &$attachmentIds = [], &$threadVideoIds = [], $withIds = false)
    {
        $inPutToms = [];
        foreach ($tomData as $threadId => $toms) {
            foreach ($toms as $tom) {
                $value = json_decode($tom['value'], true);
                if ($withIds) {
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
                }
                $inPutToms[$tom['thread_id']][$tom['key']] = $this->buildTomJson($tom['thread_id'], $tom['tom_type'], $this->SELECT_FUNC, $value);
            }
        }
        if ($withIds) {
            $attachmentIds = array_values(array_unique($attachmentIds));
            $threadVideoIds = array_values(array_unique($threadVideoIds));
        }
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
        $groupUsers = GroupUser::query()->whereIn('user_id', $userIds)->get()->keyBy('user_id')->toArray();
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
        $attachments = Attachment::query()->whereIn('id', $attachmentIds)->get()->keyBy('id');
        $attachments = $this->appendDefaultEmpty($attachmentIds, $attachments, null);
        app('cache')->put(CacheKey::LIST_THREADS_V3_ATTACHMENT, $attachments);
        return $attachments;
    }

    private function cacheVideo($threadVideoIds)
    {
        $threadVideos = ThreadVideo::query()->whereIn('id', $threadVideoIds)->where('status', ThreadVideo::VIDEO_STATUS_SUCCESS)->get()->keyBy('id')->toArray();
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
        $likedUsers = ThreadHelper::getThreadLikedDetail($threadIds, $postIds, $posts);
        app('cache')->put(CacheKey::LIST_THREADS_V3_POST_USERS, $likedUsers);
        return $likedUsers;
    }

    //点赞收藏
    private function cachePostLikedAndFavor($userId, $threadIds, $postIds)
    {
        list($postUsersLike, $postFavor) = ThreadHelper::getPostLikedAndFavor($userId, $threadIds, $postIds);
        app('cache')->put(CacheKey::LIST_THREADS_V3_POST_LIKED, $postUsersLike);//点赞
        app('cache')->put(CacheKey::LIST_THREADS_V3_POST_FAVOR, $postFavor);//收藏
        return [$postUsersLike, $postFavor];
    }
}
