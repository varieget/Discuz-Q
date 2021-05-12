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
use App\Common\ResponseCode;
use App\Models\Category;
use App\Models\Order;
use App\Models\Post;
use App\Models\Sequence;
use App\Models\Thread;
use Carbon\Carbon;
use Discuz\Base\DzqController;
use Illuminate\Support\Arr;

class ThreadListController extends DzqController
{

    use ThreadTrait;
    use ThreadListTrait;

    private $preload = false;

    public function main()
    {
        $filter = $this->inPut('filter');
        $currentPage = intval($this->inPut('page'));
        $perPage = $this->inPut('perPage');
        $sequence = $this->inPut('sequence');//默认首页
        $this->preload = boolval($this->inPut('preload'));//预加载前100页数据
        $currentPage <= 0 && $currentPage = 1;
        if (empty($sequence)) {
            $threads = $this->getFilterThreads($filter, $currentPage, $perPage);
        } else {
            $threads = $this->getDefaultHomeThreads($filter, $currentPage, $perPage);
        }
        $threadCollection = $threads['pageData'];
        $threads['pageData'] = $this->getFullThreadData($threadCollection);
        $this->outPut(0, '', $threads);
    }

    function getFilterThreads($filter, $currentPage, $perPage)
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

        if (!empty($filter['complex'])) {
            return $this->getComplex($filter, $currentPage, $perPage);
        }

        $essence = null;
        $types = [];
        $categoryids = [];
        $sort = Thread::SORT_BY_THREAD;
        $attention = 0;
        $search = '';
        isset($filter['sticky']) && $stick = $filter['sticky'];
        isset($filter['essence']) && $essence = $filter['essence'];
        isset($filter['types']) && $types = $filter['types'];
        isset($filter['categoryids']) && $categoryids = $filter['categoryids'];
        isset($filter['sort']) && $sort = $filter['sort'];
        isset($filter['attention']) && $attention = $filter['attention'];
        isset($filter['search']) && $search = $filter['search'];
        $categoryids = Category::instance()->getValidCategoryIds($this->user, $categoryids);
        if (!$categoryids) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '没有浏览权限');
        }
        $groupId = $this->groupId();
        $cacheKey = CacheKey::LIST_THREADS_V3 . $groupId;
        $cache = $this->cacheInstance();
        $threads = $this->getThreadsCache($cache, $cacheKey, $currentPage, $filter);
        if ($threads) return $threads;
        $threads = $this->getThreadsBuilder();
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
        $this->setFilterSort($threads, $sort);
        //关注
        if ($attention == 1 && !empty($this->user)) {
            $threads->leftJoin('user_follow as follow', 'follow.to_user_id', '=', 'th.user_id')
                ->where('follow.from_user_id', $this->user->id);
        }
        !empty($categoryids) && $threads->whereIn('category_id', $categoryids);
        if ($this->preload) {
            $preLoad = $this->preloadPaginiation(100, 10, $threads, false);
            $this->setThreadsCache($cache, $cacheKey, $currentPage, $filter, $preLoad);
        } else {
            $threads = $this->pagination($currentPage, $perPage, $threads, false);
        }
        return $threads;
    }


    public function getComplex($filter, $currentPage, $perPage)
    {
        switch ($filter['complex'])
        {
            case Thread::MY_DRAFT_THREAD:
                $threads = $this->getThreadsBuilder(Thread::IS_DRAFT);
                break;
            case Thread::MY_LIKE_THREAD:
                $threads = $this->getMyOrHisLikesThread($filter);
                break;
            case Thread::MY_COLLECT_THREAD:
                $threads = $this->getMyCollectThread();
                break;
            case Thread::MY_BUY_THREAD:
                $threads = $this->getMyBuyThread();
                break;
            default:
                $threads = $this->getMyOrHisThread($filter);
        }

        return $this->pagination($currentPage, $perPage, $threads, false);
    }

    //我的点赞帖子or他的点赞帖子
    public function getMyOrHisLikesThread($filter)
    {
        $UserId = $this->user->id;
        if (!empty($filter['toUserId'])) {
            $UserId = (int)$filter['toUserId'];
        }

        return $this->getThreadsBuilder()
            ->leftJoin('posts as post', 'post.thread_id', '=', 'th.id')
            ->where(['post.is_first' => Post::FIRST_YES, 'post.is_approved' => Post::APPROVED_YES])
            ->leftJoin('post_user as postu','postu.post_id','=','post.id')
            ->where(['post.user_id' => $UserId]);
    }

    //我的收藏帖子
    public function getMyCollectThread()
    {
        return $this->getThreadsBuilder()
            ->leftJoin('thread_user as thu', 'thu.thread_id', '=', 'th.id')
            ->where(['thu.user_id' => $this->user->id]);
    }

    //我购买帖子
    public function getMyBuyThread()
    {
        return $this->getThreadsBuilder()
            ->leftJoin('orders as order','order.thread_id','=','th.id')
            ->where(['order.user_id' => $this->user->id, 'status' => Order::ORDER_STATUS_PAID]);
    }

    //我的帖子or他的帖子
    public function getMyOrHisThread($filter)
    {
        $UserId = $this->user->id;
        if (!empty($filter['toUserId'])) {
            $UserId = (int)$filter['toUserId'];
        }

        return $this->getThreadsBuilder()
            ->where('user_id', $UserId);
    }


    private function getThreadsCache($cache, $cacheKey, $currentPage, $filter)
    {

        $ret = $cache->get($cacheKey);
        if ($ret) {
            $ret = unserialize($ret);
            $page = $currentPage;
            $filter = md5(serialize($filter));
            $threads = $ret[$filter][$page - 1] ?? false;
            if ($threads) {
                return $threads;
            }
        }
        return false;
    }

    /**
     * @desc 缓存前100页数据
     * @param $cache
     * @param $cacheKey
     * @param $currentPage
     * @param $filter
     * @param $threads
     */
    private function setThreadsCache($cache, $cacheKey, $currentPage, $filter, $threads)
    {
        $filter = md5(serialize($filter));
        $data = [
            $filter => $threads
        ];
        $cache->put($cacheKey, serialize($data));
    }

    private function groupId()
    {
        $groups = $this->user->groups->toArray();
        $groupIds = array_column($groups, 'id');
        return Arr::first($groupIds) ?? 0;
    }

    private function setFilterSort($threads, $sort)
    {
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
    }

    function getDefaultHomeThreads($filter, $currentPage, $perPage)
    {
        $sequence = Sequence::query()->first();
        if (empty($sequence)) {
            return $this->getFilterThreads($filter, $currentPage, $perPage);
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
        $threads = $this->getThreadsBuilder();
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
        return $this->pagination($currentPage, $perPage, $threads, false);
    }

    private function getThreadsBuilder($isDraft = Thread::IS_NOT_DRAFT)
    {
        return Thread::query()
            ->select('th.*')
            ->from('threads as th')
            ->whereNull('th.deleted_at')
            ->where('th.is_sticky', Thread::BOOL_NO)
            ->where('th.is_draft', $isDraft)
            ->where('th.is_approved', Thread::APPROVED);
    }
}
