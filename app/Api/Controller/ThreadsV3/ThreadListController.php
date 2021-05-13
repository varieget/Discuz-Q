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
use App\Common\ResponseCode;
use App\Models\Category;
use App\Models\Order;
use App\Models\Post;
use App\Models\Sequence;
use App\Models\Thread;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;

class ThreadListController extends DzqController
{

    use ThreadTrait;
    use ThreadListTrait;

    private $preload = false;
    const PRELOAD_PAGES = 50;//预加载的页数
//    private $categoryIds = [];

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
//        $filter = $this->inPut('filter') ?: [];
//        $categoryIds = $filter['categoryids'] ?? [];
//        $this->categoryIds = Category::instance()->getValidCategoryIds($this->user, $categoryIds);
//        if (!$this->categoryIds) {
//            throw new PermissionDeniedException('没有浏览权限');
//        }
        return true;
    }

    public function main()
    {
        $filter = $this->inPut('filter');
        $currentPage = intval($this->inPut('page'));
        $perPage = $this->inPut('perPage');
        $sequence = $this->inPut('sequence');//默认首页
        $this->preload = boolval($this->inPut('preload'));//预加载前100页数据
        $currentPage <= 0 && $currentPage = 1;
//        $this->openQueryLog();
        if (empty($sequence)) {
            $threads = $this->getFilterThreads($filter, $currentPage, $perPage);
        } else {
            $threads = $this->getSequenceThreads($filter, $currentPage, $perPage);
        }
        $pageData = $threads['pageData'];
        //缓存中获取最新的threads
        $pageData = $this->getThreadsFromCache(array_column($pageData, 'id'));
        $threads['pageData'] = $this->getFullThreadData($pageData);
//        $this->closeQueryLog();
        $this->outPut(0, '', $threads);
    }

    /**
     * @desc 按照首页帖子id顺序从缓存中依次取出最新帖子数据
     * 首页数据缓存只存帖子id
     * @param $threadIds
     * @return array
     */
    private function getThreadsFromCache($threadIds)
    {
        $pageData = DzqCache::extractCacheArrayData(CacheKey::LIST_THREADS_V3_THREADS, $threadIds, function ($threadIds) {
            return Thread::query()->whereIn('id', $threadIds)->get()->keyBy('id')->toArray();
        });
        $threads = [];
        foreach ($threadIds as $threadId) {
            $threads[] = $pageData[$threadId] ?? null;
        }
        return $threads;
    }


    private function getFilterThreads($filter, $page, $perPage)
    {
        $cacheKey = CacheKey::LIST_THREADS_V3_1;
        $filterId = md5(serialize($this->inPut('filter')));
//        if ($page == 1) {//第一页检查是否需要初始化缓存
        if ($this->preload || $page == 1) {//第一页检查是否需要初始化缓存
            $threads = DzqCache::extractThreadListData($cacheKey, $filterId, $page, function () use ($cacheKey, $filter, $page, $perPage) {
                $threads = $this->buildFilterThreads($filter);
                $threads = $this->preloadPaginiation(self::PRELOAD_PAGES, 10, $threads, true);
                $this->initDzqThreadsData($cacheKey, $threads);
                return $threads;
            }, true);
        } else {//其他页从缓存取，取不到就重数据库取并写入缓存
            $threads = DzqCache::extractThreadListData($cacheKey, $filterId, $page, function ($page) use ($filter, $perPage) {
                $threads = $this->buildFilterThreads($filter);
                $threads = $this->pagination($page, $perPage, $threads, true);
                return $threads;
            });
        }
        return $threads;
    }


    function getSequenceThreads($filter, $page, $perPage)
    {
        $cacheKey = CacheKey::LIST_THREADS_V3_0;
        $filterId = md5(serialize($this->inPut('filter')));
        if ($this->preload || $page == 1) {//第一页检查是否需要初始化缓存
            $threads = DzqCache::extractThreadListData($cacheKey, $filterId, $page, function () use ($cacheKey, $filter, $page, $perPage) {
                $threads = $this->buildSequenceThreads($filter);
                $threads = $this->preloadPaginiation(self::PRELOAD_PAGES, 10, $threads, true);
                $this->initDzqThreadsData($cacheKey, $threads);
                return $threads;
            }, true);
        } else {
            $threads = DzqCache::extractThreadListData($cacheKey, $filterId, $page, function ($page) use ($filter, $perPage) {
                $threads = $this->buildSequenceThreads($filter);
                $threads = $this->pagination($page, $perPage, $threads, true);
                return $threads;
            });
        }
        return $threads;
    }

    /**
     * @desc 普通筛选SQL
     * @param $filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildFilterThreads($filter)
    {
        if (empty($filter)) $filter = [];
        $this->dzqValidate($filter, [
            'sticky' => 'integer|in:0,1',
            'essence' => 'integer|in:0,1',
            'types' => 'array',
            'categoryids' => 'array',
            'sort' => 'integer|in:1,2,3',
            'attention' => 'integer|in:0,1',
            'complex' => 'integer|in:1,2,3,4'
        ]);
        $loginUserId = $this->user->id;
        $essence = null;
        $types = [];
        $categoryids = [];
        $sort = Thread::SORT_BY_THREAD;
        $attention = 0;
        $search = '';
        $complex = '';
        isset($filter['sticky']) && $stick = $filter['sticky'];
        isset($filter['essence']) && $essence = $filter['essence'];
        isset($filter['types']) && $types = $filter['types'];
        isset($filter['categoryids']) && $categoryids = $filter['categoryids'];
        isset($filter['sort']) && $sort = $filter['sort'];
        isset($filter['attention']) && $attention = $filter['attention'];
        isset($filter['search']) && $search = $filter['search'];
        isset($filter['complex']) && $complex = $filter['complex'];

        $categoryids = Category::instance()->getValidCategoryIds($this->user, $categoryids);
        if (!$categoryids && empty($complex)) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '没有浏览权限');
        }
        $threads = $this->getBaseThreadsBuilder();
        if (!empty($complex)) {
            switch ($complex) {
                case Thread::MY_DRAFT_THREAD:
                    $threads = $this->getBaseThreadsBuilder(Thread::IS_DRAFT);
                    break;
                case Thread::MY_LIKE_THREAD:
                    empty($filter['toUserId']) ? $userId = $loginUserId : $userId = intval($filter['toUserId']);
                    $threads = $threads->leftJoin('posts as post', 'post.thread_id', '=', 'th.id')
                        ->where(['post.is_first' => Post::FIRST_YES, 'post.is_approved' => Post::APPROVED_YES])
                        ->leftJoin('post_user as postu', 'postu.post_id', '=', 'post.id')
                        ->where(['post.user_id' => $userId]);
                    break;
                case Thread::MY_COLLECT_THREAD:
                    $threads = $threads->leftJoin('thread_user as thu', 'thu.thread_id', '=', 'th.id')
                        ->where(['thu.user_id' => $loginUserId]);
                    break;
                case Thread::MY_BUY_THREAD:
                    $threads = $threads->leftJoin('orders as order', 'order.thread_id', '=', 'th.id')
                        ->where(['order.user_id' => $loginUserId, 'status' => Order::ORDER_STATUS_PAID]);
                    break;
                default:
                    empty($filter['toUserId']) ? $userId = $loginUserId : $userId = intval($filter['toUserId']);
                    $threads = $threads->where('user_id', $userId);
            }
        }
        !empty($essence) && $threads = $threads->where('is_essence', $essence);
        if (!empty($types)) {
            $threads = $threads->leftJoin('thread_tag as tag', 'tag.thread_id', '=', 'th.user_id')
                ->whereIn('tag', $types);
        }
        if (!empty($search)) {
            $threads = $threads->leftJoin('posts as post', 'th.id', '=', 'post.thread_id')
                ->addSelect('post.content')
                ->where(['post.is_first' => Post::FIRST_YES, 'post.is_approved' => Post::APPROVED_YES])
                ->whereNull('post.deleted_at')
                ->where('post.content', 'like', '%' . $search . '%');
        }
        if (!empty($sort)) {
            switch ($sort) {
                case Thread::SORT_BY_THREAD://按照发帖时间排序
                    $threads->orderByDesc('th.id');
                    break;
                case Thread::SORT_BY_POST://按照评论时间排序
                    $threads->orderByDesc('th.posted_at');
                    break;
                case Thread::SORT_BY_HOT://按照热度排序
                    $threads->whereBetween('th.created_at', [Carbon::parse('-7 days'), Carbon::now()]);
                    $threads->orderByDesc('th.view_count');
                    break;
                default:
                    $threads->orderByDesc('th.id');
                    break;
            }
        }
        //关注
        if ($attention == 1 && !empty($this->user)) {
            $threads->leftJoin('user_follow as follow', 'follow.to_user_id', '=', 'th.user_id')
                ->where('follow.from_user_id', $this->user->id);
        }
        !empty($categoryids) && $threads->whereIn('category_id', $categoryids);
        return $threads;
    }

    /**
     * @desc 智能排序SQL
     * @param $filter
     * @return bool|\Illuminate\Database\Eloquent\Builder
     */
    private function buildSequenceThreads($filter)
    {
        $sequence = Sequence::getSequence();
        if (empty($sequence)) {
            return false;
        }
        $categoryIds = [];
        !empty($sequence['category_ids']) && $categoryIds = explode(',', $sequence['category_ids']);
        $categoryIds = Category::instance()->getValidCategoryIds($this->user, $categoryIds);
        if (!$categoryIds) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '没有浏览权限');
        }

        if (empty($filter)) $filter = [];
        isset($filter['types']) && $types = $filter['types'];

        !empty($sequence['group_ids']) && $groupIds = explode(',', $sequence['group_ids']);
        !empty($sequence['user_ids']) && $userIds = explode(',', $sequence['user_ids']);
        !empty($sequence['topic_ids']) && $topicIds = explode(',', $sequence['topic_ids']);
        !empty($sequence['thread_ids']) && $threadIds = explode(',', $sequence['thread_ids']);
        !empty($sequence['block_user_ids']) && $blockUserIds = explode(',', $sequence['block_user_ids']);
        !empty($sequence['block_topic_ids']) && $blockTopicIds = explode(',', $sequence['block_topic_ids']);
        !empty($sequence['block_thread_ids']) && $blockThreadIds = explode(',', $sequence['block_thread_ids']);
        $threads = $this->getBaseThreadsBuilder();
        if (!empty($categoryIds)) {
            $threads = $threads->whereIn('th.category_id', $categoryIds);
        }
        if (!empty($types)) {
            $threads = $threads->leftJoin('thread_tag as tag', 'tag.thread_id', '=', 'th.user_id')
                ->whereIn('tag', $types);
        }
        if (!empty($groupIds)) {
            $threads = $threads
                ->leftJoin('group_user as g1', 'g1.user_id', '=', 'th.user_id')
                ->whereIn('g1.group_id', $groupIds);
        }
        if (!empty($topicIds)) {
            $threads = $threads
                ->leftJoin('thread_topic as topic', 'topic.thread_id', '=', 'th.id')
                ->whereIn('topic.topic_id', $topicIds);
        }
        if (!empty($userIds)) {
            $threads = $threads->whereIn('th.user_id', $userIds);
        }
        if (!empty($threadIds)) {
            $threads = $threads->whereIn('th.id', $threadIds);
        }
        if (!empty($blockUserIds)) {
            $threads->whereNotExists(function ($query) use ($blockUserIds) {
                $query->whereIn('th.user_id', $blockUserIds);
            });
        }
        if (!empty($blockThreadIds)) {
            $threads->whereNotExists(function ($query) use ($blockThreadIds) {
                $query->whereIn('th.id', $blockThreadIds);
            });
        }
        if (!empty($blockTopicIds)) {
            $threads->whereNotExists(function ($query) use ($blockTopicIds) {
                $query->whereIn('topic.topic_id', $blockTopicIds);
            });
        }
        $threads = $threads->orderByDesc('th.created_at');
        return $threads;
    }

    private function getBaseThreadsBuilder($isDraft = Thread::BOOL_NO)
    {
        return Thread::query()
            ->select('th.*')
            ->from('threads as th')
            ->whereNull('th.deleted_at')
            ->where('th.is_sticky', Thread::BOOL_NO)
            ->where('th.is_draft', $isDraft)
            ->where('th.is_approved', Thread::BOOL_YES);
    }
}
