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
use App\Models\Thread;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Discuz\Contracts\Setting\SettingsRepository;

class ThreadListController extends DzqController
{

    use ThreadTrait;
    use ThreadListTrait;
    use ThreadQueryTrait;

    private $preload = false;
    const PRELOAD_PAGES = 20;//预加载的页数

    private $preloadCount = 0;
    private $categoryIds = [];

    private $viewHotList = false;
    protected $settings;

    private $group = null;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $filter = $this->inPut('filter') ?: [];
        $categoryIds = $filter['categoryids'] ?? [];
        $complex = $filter['complex'] ?? null;
        $user = $this->user;
        $this->group = $user->groups->toArray();
        $this->viewHotList();
        $this->categoryIds = Category::instance()->getValidCategoryIds($this->user, $categoryIds);
        if (!$this->viewHotList) {
            if (!$this->categoryIds) {
                if (empty($complex) ||
                    $complex == Thread::MY_LIKE_THREAD ||
                    $complex == Thread::MY_COLLECT_THREAD) {
                    throw new PermissionDeniedException('没有浏览权限');
                }
                //自己的主题去除分类权限控制
                if ($complex == Thread::MY_OR_HIS_THREAD) {
                    if ($user->id !== $filter['toUserId'] && !empty($filter['toUserId'])){
                        throw new PermissionDeniedException('没有浏览权限');
                    }
                    $this->categoryIds = array();
                }
            }
            //去除购买帖子的分类控制
            if ($complex == Thread::MY_BUY_THREAD) {
                $this->categoryIds = array();
            }
        }
        return true;
    }

    private function viewHotList()
    {
        $group = $this->group;
        $group = current($group);
        $paid = boolval($this->inPut('pay'));
        if (!empty($group)) {
            if (($group['id'] == Group::UNPAID || $group['id'] == Group::GUEST_ID) && $paid) {
                $this->viewHotList = true;
                return;
            }
        }
        $this->viewHotList = false;
        return;
    }


    public function main()
    {
        $filter = $this->inPut('filter');
        $page = intval($this->inPut('page'));
        $perPage = intval($this->inPut('perPage'));
        $sequence = $this->inPut('sequence');//默认首页
        $this->preload = boolval($this->inPut('preload'));//预加载前100页数据
        $page <= 0 && $page = 1;
        if ($this->viewHotList) {
            $page = 1;
            $sequence = 0;
            $perPage = 10;
            $filter['sort'] = Thread::SORT_BY_HOT;
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
        $threads['pageData'] = $this->getFullThreadData($pageData, true);
//        $this->info('query_sql_log', app(\Illuminate\Database\ConnectionInterface::class)->getQueryLog());
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
        return $this->loadPageThreads($cacheKey, $filterKey, $page, $threadsBuilder, $filter, $perPage);
    }

    function getSequenceThreads($filter, $page, $perPage)
    {
        $threadsBuilder = $this->buildSequenceThreads($filter);
        $cacheKey = CacheKey::LIST_THREADS_V3_SEQUENCE;
        $filterKey = $this->filterKey($perPage, $filter);
        return $this->loadPageThreads($cacheKey, $filterKey, $page, $threadsBuilder, $filter, $perPage);
    }

    private function loadPageThreads($cacheKey, $filterKey, $page, $threadsBuilder, $filter, $perPage)
    {
        if ($page == 1 && !$this->viewHotList) {
            $this->loadAllPage($cacheKey, $filterKey, $page, $threadsBuilder, $filter, $perPage);
        }
        return $this->loadOnePage($cacheKey, $filterKey, $page, $threadsBuilder, $filter, $perPage);
    }

    private function loadAllPage($cacheKey, $filterKey, $page, $threadsBuilder, $filter, $perPage)
    {
        if ($page != 1) {
            return false;
        }
        return DzqCache::hM2Get($cacheKey, $filterKey, $page, function () use ($threadsBuilder, $cacheKey, $filter, $page, $perPage) {
            $threads = $this->preloadPaginiation(self::PRELOAD_PAGES, $perPage, $threadsBuilder);
            $this->initDzqGlobalData($threads);
            array_walk($threads, function (&$v) {
                $v['pageData'] = array_column($v['pageData'], 'id');
            });
            return $threads;
        }, true);
    }

    private function loadOnePage($cacheKey, $filterKey, $page, $threadsBuilder, $filter, $perPage)
    {
        return DzqCache::hM2Get($cacheKey, $filterKey, $page, function () use ($threadsBuilder, $filter, $page, $perPage) {
            $threads = $this->pagination($page, $perPage, $threadsBuilder, true);
            $threads['pageData'] = array_column($threads['pageData'], 'id');
            return $threads;
        });
    }

    /**
     * @desc 普通筛选SQL
     * @param $filter
     * @param bool $withLoginUser
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildFilterThreads($filter, &$withLoginUser = false)
    {
        list($essence, $types, $sort, $attention, $search, $complex,$categoryids) = $this->initFilter($filter);
        $loginUserId = $this->user->id;
        $administrator = $this->user->isAdmin();
        $threads = $this->getBaseThreadsBuilder();
        if (!empty($complex)) {
            switch ($complex) {
                case Thread::MY_DRAFT_THREAD:
                    $threads = $this->getBaseThreadsBuilder(Thread::IS_DRAFT,false)
                        ->where('th.user_id', $loginUserId)
                        ->orderByDesc('th.id');
                    $threads = $threads->join('posts as post', 'post.thread_id', '=', 'th.id');
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
                        ->whereIn('order.type', [Order::ORDER_TYPE_THREAD, Order::ORDER_TYPE_ATTACHMENT])
                        ->where(['order.user_id' => $loginUserId, 'order.status' => Order::ORDER_STATUS_PAID])
                        ->orderByDesc('order.updated_at');
                    break;
                case Thread::MY_OR_HIS_THREAD:
                    if (empty($filter['toUserId']) || $filter['toUserId'] == $loginUserId || $administrator) {
                        $threads = $this->getBaseThreadsBuilder(Thread::BOOL_NO, false);
                    } else {
                        $threads = $threads->where('th.is_anonymous', Thread::IS_NOT_ANONYMOUS);
                    }
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
                ->where(function ($threads) use ($search) {
                    $threads->where('th.title', 'like', '%' . $search . '%');
                    $threads->orWhere('post.content', 'like', '%' . $search . '%');
                });
        }
        if (!empty($sort)) {
            switch ($sort) {
                case Thread::SORT_BY_THREAD://按照发帖时间排序
                    $threads->orderByDesc('th.created_at');
                    break;
                case Thread::SORT_BY_POST://按照评论时间排序
                    $threads->orderByDesc('th.posted_at');
                    break;
                case Thread::SORT_BY_HOT://按照热度排序
                    $threads->whereBetween('th.created_at', [Carbon::parse('-7 days'), Carbon::now()]);
                    $threads->orderByDesc('th.view_count');
                    break;
                case Thread::SORT_BY_RENEW://按照更新时间排序
                    $threads->orderByDesc('th.updated_at');
                    break;
                default:
                    $threads->orderByDesc('th.id');
                    break;
            }
        }
        //关注
        if ($attention == 1 && !empty($this->user)) {
            $threads->leftJoin('user_follow as follow', 'follow.to_user_id', '=', 'th.user_id')
                ->where('th.is_anonymous',Thread::BOOL_NO)
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

        if(!empty($site)){
            $threads = $threads->where('th.is_site', Thread::IS_SITE);
        }
        if(!empty($exclusiveIds)){
            $threads = $threads->whereNotIn('th.id', $exclusiveIds);
        }
        !empty($categoryids) && $threads->whereIn('category_id', $categoryids);
        return $threads;
    }

    /**
     * @desc 筛选变量
     * @param $filter
     * @return array
     */
    private function initFilter($filter)
    {
        empty($filter) && $filter = [];
        $this->dzqValidate($filter, [
            'essence' => 'integer|in:0,1',
            'types' => 'array',
            'sort' => 'integer|in:1,2,3,4',
            'attention' => 'integer|in:0,1',
            'complex' => 'integer|in:1,2,3,4,5',
            'site' => 'integer|in:0,1',
            'exclusiveIds' => 'array',
            'categoryids' => 'array'
        ]);
        $essence = '';
        $types = [];
        $sort = Thread::SORT_BY_THREAD;
        $attention = 0;
        $search = '';
        $complex = '';
        isset($filter['essence']) && $essence = $filter['essence'];
        isset($filter['types']) && $types = $filter['types'];
        isset($filter['sort']) && $sort = $filter['sort'];
        isset($filter['attention']) && $attention = $filter['attention'];
        isset($filter['search']) && $search = $filter['search'];
        isset($filter['complex']) && $complex = $filter['complex'];
        isset($filter['site']) && $site = $filter['site'];
        isset($filter['exclusiveIds']) && $exclusiveIds = $filter['exclusiveIds'];
        $categoryids = $this->categoryIds;
        return [$essence, $types, $sort, $attention, $search, $complex,$categoryids];
    }


    private function getBaseThreadsBuilder($isDraft = Thread::BOOL_NO,$filterApprove = true)
    {
        $threads =  Thread::query()
            ->select('th.*')
            ->from('threads as th')
            ->whereNull('th.deleted_at')
            ->whereNotNull('th.user_id')
            ->where('th.is_draft', $isDraft)
            ->where('th.is_display', Thread::BOOL_YES);
        if($filterApprove){
            $threads->where('th.is_approved', Thread::BOOL_YES);
        }
        return $threads;
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
        $serialize = ['perPage' => $perPage, 'filter' => $filter,'group'=>$this->group];
        $withLoginUser && $serialize['user'] = $this->user->id;
        return md5(serialize($serialize));
    }

}
