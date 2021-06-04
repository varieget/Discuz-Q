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
use App\Models\DenyUser;
use App\Models\Group;
use Discuz\Base\DzqCache;
use App\Models\Category;
use App\Models\Order;
use App\Models\Post;
use App\Models\Sequence;
use App\Models\Thread;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Database\ConnectionInterface;

class ThreadListController extends DzqController
{

    use ThreadTrait;
    use ThreadListTrait;

    private $preload = false;
    const PRELOAD_PAGES = 30;//预加载的页数

    private $preloadCount = 0;
    private $categoryIds = [];

    protected $settings;


    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $filter = $this->inPut('filter') ?: [];
        $categoryIds = $filter['categoryids'] ?? [];
        $complex = $filter['complex'] ?? null;

        $this->categoryIds = Category::instance()->getValidCategoryIds($this->user, $categoryIds);
        if (!$this->isUnPaid()) {
            if (!$this->categoryIds && empty($complex)) {
                throw new PermissionDeniedException('没有浏览权限');
            }
        }
        return true;
    }

    private function isUnPaid()
    {
        $groups = $this->user->groups->toArray();
        $group = current($groups);
        if (!empty($group) && $group['id'] == Group::UNPAID) {
            return true;
        }
        return false;
    }


    public function main()
    {
        $filter = $this->inPut('filter');
        $page = intval($this->inPut('page'));
        $perPage = $this->inPut('perPage');
        $sequence = $this->inPut('sequence');//默认首页
        $this->preload = boolval($this->inPut('preload'));//预加载前100页数据
        $page <= 0 && $page = 1;
        if ($this->isUnPaid()) {
            $page = 1;
            $sequence = 0;
            $perPage = 10;
            $filter = ['sort' => Thread::SORT_BY_HOT];
        }
//        $this->openQueryLog();
        $this->preloadCount = self::PRELOAD_PAGES * $perPage;
        if (empty($sequence)) {
            $threads = $this->getFilterThreads($filter, $page, $perPage);
        } else {
            $threads = $this->getSequenceThreads($filter, $page, $perPage);
        }
        $threadIds = $threads['pageData'];
        //缓存中获取最新的threads
        $pageData = $this->getThreads($threadIds);
        $threads['pageData'] = $this->getFullThreadData($pageData);
//        $this->info('query_sql_log', app(ConnectionInterface::class)->getQueryLog());
//        $this->closeQueryLog();
        $this->outPut(0, '', $threads);
    }

    /**
     * @desc 按照首页帖子id顺序从缓存中依次取出最新帖子数据
     * 首页数据缓存只存帖子id
     * @param $threadIds
     * @return array
     */
    private function getThreads($threadIds)
    {
        $pageData = DzqCache::hMGet(CacheKey::LIST_THREADS_V3_THREADS, $threadIds, function ($threadIds) {
            return Thread::query()->whereIn('id', $threadIds)->get()->toArray();
        }, 'id');
        $threads = [];
        foreach ($threadIds as $threadId) {
            $threads[] = $pageData[$threadId] ?? null;
        }
        return $threads;
    }


    private function getFilterThreads($filter, $page, $perPage)
    {
        $threadsBuilder = $this->buildFilterThreads($filter, $withLoginUser);
        $cacheKey = $this->cacheKey($filter);
        $filterKey = $this->filterKey($perPage, $filter, $withLoginUser);
        //初始化exist数据
        if ($this->preload || $page == 1) {//第一页检查是否需要初始化缓存
            $threads = DzqCache::hM2Get($cacheKey, $filterKey, $page, function () use ($threadsBuilder, $cacheKey, $filter, $page, $perPage) {
                $threads = $this->preloadPaginiation(self::PRELOAD_PAGES, $perPage, $threadsBuilder);
                $this->initDzqGlobalData($threads);
                array_walk($threads, function (&$v) {
                    $v['pageData'] = array_column($v['pageData'], 'id');
                });
                return $threads;
            }, true);
            $this->initDzqUserData($this->user->id, $cacheKey, $filterKey, $this->preloadCount);
        } else {//其他页从缓存取，取不到就重数据库取并写入缓存
            $threads = DzqCache::hM2Get($cacheKey, $filterKey, $page, function () use ($threadsBuilder, $filter, $page, $perPage) {
                $threads = $this->pagination($page, $perPage, $threadsBuilder, true);
                $threads['pageData'] = array_column($threads['pageData'], 'id');
                return $threads;
            });
        }
        return $threads;
    }

    function getSequenceThreads($filter, $page, $perPage)
    {
        $threadsBuilder = $this->buildSequenceThreads($filter);
        $cacheKey = CacheKey::LIST_THREADS_V3_SEQUENCE;
        $filterKey = $this->filterKey($perPage, $filter);
        if ($this->preload || $page == 1) {//第一页检查是否需要初始化缓存
            $threads = DzqCache::hM2Get($cacheKey, $filterKey, $page, function () use ($threadsBuilder, $cacheKey, $filter, $page, $perPage) {
                $threads = $this->preloadPaginiation(self::PRELOAD_PAGES, $perPage, $threadsBuilder);
                $this->initDzqGlobalData($threads);
                array_walk($threads, function (&$v) {
                    $v['pageData'] = array_column($v['pageData'], 'id');
                });
                return $threads;
            }, true);
            $this->initDzqUserData($this->user->id, $cacheKey, $filterKey, $this->preloadCount);
        } else {
            $threads = DzqCache::hM2Get($cacheKey, $filterKey, $page, function () use ($threadsBuilder, $filter, $page, $perPage) {
                $threads = $this->pagination($page, $perPage, $threadsBuilder, true);
                $threads['pageData'] = array_column($threads['pageData'], 'id');
                return $threads;
            });
        }
        return $threads;
    }


    /**
     * @desc 普通筛选SQL
     * @param $filter
     * @param bool $withLoginUser
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildFilterThreads($filter, &$withLoginUser = false)
    {
        if (empty($filter)) $filter = [];
        $this->dzqValidate($filter, [
            'sticky' => 'integer|in:0,1',
            'essence' => 'integer|in:0,1',
            'types' => 'array',
            'categoryids' => 'array',
            'sort' => 'integer|in:1,2,3',
            'attention' => 'integer|in:0,1',
            'complex' => 'integer|in:1,2,3,4,5'
        ]);
        $loginUserId = $this->user->id;
        $essence = null;
        $types = [];
//        $categoryids = [];
        $sort = Thread::SORT_BY_THREAD;
        $attention = 0;
        $search = '';
        $complex = '';
        isset($filter['sticky']) && $stick = $filter['sticky'];
        isset($filter['essence']) && $essence = $filter['essence'];
        isset($filter['types']) && $types = $filter['types'];
//        isset($filter['categoryids']) && $categoryids = $filter['categoryids'];
        isset($filter['sort']) && $sort = $filter['sort'];
        isset($filter['attention']) && $attention = $filter['attention'];
        isset($filter['search']) && $search = $filter['search'];
        isset($filter['complex']) && $complex = $filter['complex'];

        $categoryids = $this->categoryIds;
        $threads = $this->getBaseThreadsBuilder();
        if (!empty($complex)) {
            switch ($complex) {
                case Thread::MY_DRAFT_THREAD:
                    $threads = $this->getBaseThreadsBuilder(Thread::IS_DRAFT)
                        ->where('user_id', $loginUserId)
                        ->orderByDesc('th.id');
                    break;
                case Thread::MY_LIKE_THREAD:
                    empty($filter['toUserId']) ? $userId = $loginUserId : $userId = intval($filter['toUserId']);
                    $threads = $threads->leftJoin('posts as post', 'post.thread_id', '=', 'th.id')
                        ->where(['post.is_first' => Post::FIRST_YES, 'post.is_approved' => Post::APPROVED_YES])
                        ->leftJoin('post_user as postu', 'postu.post_id', '=', 'post.id')
                        ->where(['postu.user_id' => $userId])
                        ->orderByDesc('postu.created_at');
                    break;
                case Thread::MY_COLLECT_THREAD:
                    $threads = $threads->leftJoin('thread_user as thu', 'thu.thread_id', '=', 'th.id')
                        ->where(['thu.user_id' => $loginUserId])
                        ->orderByDesc('thu.created_at');
                    break;
                case Thread::MY_BUY_THREAD:
                    $threads = $threads->leftJoin('orders as order', 'order.thread_id', '=', 'th.id')
                        ->where(['order.user_id' => $loginUserId, 'status' => Order::ORDER_STATUS_PAID])
                        ->orderByDesc('order.updated_at');
                    break;
                case Thread::MY_OR_HIS_THREAD:
                    empty($filter['toUserId']) ? $userId = $loginUserId : $userId = intval($filter['toUserId']);
                    $threads = $threads->where('user_id', $userId)
                        ->orderByDesc('th.id');
                    break;
            }
            $withLoginUser = true;
        }
        !empty($essence) && $threads = $threads->where('is_essence', $essence);
        if (!empty($types)) {
            $threads = $threads->leftJoin('thread_tag as tag', 'tag.thread_id', '=', 'th.id')
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
            $withLoginUser = true;
        }
        //deny用户
        if (!empty($loginUserId)) {
            $denyUserIds = DenyUser::query()->where('user_id', $loginUserId)->get()->pluck('deny_user_id')->toArray();
            if (!empty($denyUserIds)) {
                $threads = $threads->whereNotIn('th.user_id', $denyUserIds);
                $withLoginUser = true;
            }
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
            return $this->buildFilterThreads($filter);
        }
        $categoryIds = [];
        !empty($sequence['category_ids']) && $categoryIds = explode(',', $sequence['category_ids']);
        $categoryIds = Category::instance()->getValidCategoryIds($this->user, $categoryIds);
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
            $threads = $threads->leftJoin('thread_tag as tag', 'tag.thread_id', '=', 'th.id')
                ->whereIn('tag.tag', $types);
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

    private function cacheKey($filter)
    {
        $sort = Thread::SORT_BY_THREAD;
        isset($filter['sort']) && $sort = $filter['sort'];
        $cacheKey = CacheKey::LIST_THREADS_V3_CREATE_TIME;
        switch ($sort) {
            case Thread::SORT_BY_POST://按照评论时间排序
                $cacheKey = CacheKey::LIST_THREADS_V3_POST_TIME;
                break;
            case Thread::SORT_BY_HOT://按照热度排序
                $cacheKey = CacheKey::LIST_THREADS_V3_VIEW_COUNT;
                break;
        }
        if (isset($filter['attention']) && $filter['attention'] == 1) {
            $cacheKey = CacheKey::LIST_THREADS_V3_ATTENTION;
        }
        if (isset($filter['complex'])) {
            $cacheKey = CacheKey::LIST_THREADS_V3_COMPLEX;
        }
        return $cacheKey;
    }

    private function filterKey($perPage, $filter, $withLoginUser = false)
    {
        $serialize = ['perPage' => $perPage, 'filter' => $filter];
        $withLoginUser && $serialize['user'] = $this->user->id;
        return md5(serialize($serialize));
    }

}
