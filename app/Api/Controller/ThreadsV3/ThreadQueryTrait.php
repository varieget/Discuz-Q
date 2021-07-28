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


use App\Models\Category;
use App\Models\DenyUser;
use App\Models\Post;
use App\Models\Sequence;
use App\Models\Thread;
use Carbon\Carbon;

trait ThreadQueryTrait
{
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

        $query = $this->getBaseThreadsBuilder();
        $query->leftJoin('group_user as g1', 'g1.user_id', '=', 'th.user_id');
        $query->leftJoin('thread_topic as topic', 'topic.thread_id', '=', 'th.id');

        if (!empty($types)) {
            $query->leftJoin('thread_tag as tag', 'tag.thread_id', '=', 'th.id')
                ->whereIn('tag.tag', $types);
        }

        if (!empty($categoryIds)) {
            $query->whereIn('th.category_id', $categoryIds);
        }

        foreach ($sequence as $key => $value) {
            if (!empty($value)) {
                if ($key == 'group_ids') {
                    $query->whereIn('g1.group_id', $groupIds);
                    $groupIds = [];
                }
                if ($key == 'topic_ids') {
                    $query->whereIn('topic.topic_id', $topicIds);
                    $topicIds = [];
                }
                if ($key == 'user_ids') {
                    $query->whereIn('th.user_id', $userIds);
                    $userIds = [];
                }
                if ($key == 'thread_ids') {
                    $query->whereIn('th.id', $threadIds);
                    $threadIds = [];
                }
                break;
            }
        }

        if (!empty($groupIds)) {
            $query->orWhereIn('g1.group_id', $groupIds);
        }
        if (!empty($topicIds)) {
            $query->orWhereIn('topic.topic_id', $topicIds);
        }
        if (!empty($userIds)) {
            $query->orWhereIn('th.user_id', $userIds);
        }
        if (!empty($threadIds)) {
            $query->orWhereIn('th.id', $threadIds);
        }
        if (!empty($blockUserIds)) {
            $query->whereNotIn('th.user_id', $blockUserIds);
        }
        if (!empty($blockThreadIds)) {
            $query->whereNotIn('th.id', $blockThreadIds);
        }
        if (!empty($blockTopicIds)) {
            $query->whereNotIn('topic.topic_id', $blockTopicIds);
        }

        $query->orderBy('th.created_at', 'desc');
        return $query;
    }


    /**
     * @desc 发现页搜索结果数据
     * @param $filter
     * @param bool $withLoginUser
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildSearchThreads($filter, &$withLoginUser = false)
    {
        list($essence, $types, $sort, $attention, $search, $complex, $categoryids) = $this->initFilter($filter);
        $loginUserId = $this->user->id;
        $threadsByHot = $this->getBaseThreadsBuilder();
        if (!empty($search)) {
            $threadsByHot->leftJoin('posts as post', 'th.id', '=', 'post.thread_id')
                ->addSelect('post.content')
                ->where(['post.is_first' => Post::FIRST_YES, 'post.is_approved' => Post::APPROVED_YES])
                ->whereNull('post.deleted_at')
                ->where(function ($threads) use ($search) {
                    $threads->where('th.title', 'like', '%' . $search . '%');
                    $threads->orWhere('post.content', 'like', '%' . $search . '%');
                });
        }
        if (!empty($loginUserId)) {
            $denyUserIds = DenyUser::query()->where('user_id', $loginUserId)->get()->pluck('deny_user_id')->toArray();
            if (!empty($denyUserIds)) {
                $threadsByHot->whereNotIn('th.user_id', $denyUserIds);
                $withLoginUser = true;
            }
        }
        !empty($categoryids) && $threadsByHot->whereIn('category_id', $categoryids);

        $threadsByHot->whereBetween('th.created_at', [Carbon::parse('-7 days'), Carbon::now()])
            ->orderByDesc('th.view_count')->limit(10)->offset(0);
        $threadsByHotIds = $threadsByHot->get()->pluck('id');
        $threadsByUpdate = $this->getBaseThreadsBuilder();
        if (!empty($search)) {
            $threadsByUpdate->leftJoin('posts as post', 'th.id', '=', 'post.thread_id')
                ->addSelect('post.content')
                ->where(['post.is_first' => Post::FIRST_YES, 'post.is_approved' => Post::APPROVED_YES])
                ->whereNull('post.deleted_at')
                ->where(function ($threads) use ($search) {
                    $threads->where('th.title', 'like', '%' . $search . '%');
                    $threads->orWhere('post.content', 'like', '%' . $search . '%');
                });
        }
        if (!empty($loginUserId)) {
            $denyUserIds = DenyUser::query()->where('user_id', $loginUserId)->get()->pluck('deny_user_id')->toArray();
            if (!empty($denyUserIds)) {
                $threadsByUpdate->whereNotIn('th.user_id', $denyUserIds);
                $withLoginUser = true;
            }
        }
        $threadsByUpdate->whereNotIn('id', $threadsByHotIds);
        !empty($categoryids) && $threadsByUpdate->whereIn('category_id', $categoryids);
        $threadsByUpdate->orderByDesc('th.updated_at')->limit(9999999999);
        return $threadsByHot->unionAll($threadsByUpdate->getQuery());
    }


    /**
     * @desc 付费站首页帖子数据,最多显示10条
     */
    private function buildPaidHomePageThreads()
    {
        $maxCount = 10;
        $threadsBySite = $this->getBaseThreadsBuilder();
        $threadsBySite->where('th.is_site', Thread::IS_SITE);
        $threadsBySite->orderByDesc('th.view_count');
        if ($threadsBySite->count() >= $maxCount) {
            return $threadsBySite;
        }
        $threadsBySiteIds = $threadsBySite->get()->pluck('id');
        $threadsByHot = $this->getBaseThreadsBuilder();
        $threadsByHot->whereBetween('th.created_at', [Carbon::parse('-7 days'), Carbon::now()])
            ->whereNotIn('id', $threadsBySiteIds)
            ->orderByDesc('th.view_count')
            ->limit($maxCount)->offset(0);
        $threadsBySite->unionAll($threadsByHot->getQuery());
        return $threadsBySite;
    }

    private function getBaseThreadsBuilder($isDraft = Thread::BOOL_NO, $filterApprove = true)
    {
        $threads = Thread::query()
            ->select('th.*')
            ->from('threads as th')
            ->whereNull('th.deleted_at')
            ->whereNotNull('th.user_id')
            ->where('th.is_draft', $isDraft)
            ->where('th.is_display', Thread::BOOL_YES);
        if ($filterApprove) {
            $threads->where('th.is_approved', Thread::BOOL_YES);
        }
        return $threads;
    }
}
