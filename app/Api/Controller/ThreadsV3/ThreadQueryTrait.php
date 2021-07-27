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
use App\Models\Sequence;

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
     */
    private function buildSearchThreads($filter)
    {
        if (empty($filter)) $filter = [];
        $this->dzqValidate($filter, [
            'sticky' => 'integer|in:0,1',
            'essence' => 'integer|in:0,1',
            'types' => 'array',
            'categoryids' => 'array',
            'sort' => 'integer|in:1,2,3,4',
            'attention' => 'integer|in:0,1',
            'complex' => 'integer|in:1,2,3,4,5',
            'site' => 'integer|in:0,1',
            'repeatedIds'=>'array'
        ]);

    }

    /**
     * @desc 付费站首页帖子数据
     * @param $filter
     */
    private function buildPayPageThreads($filter)
    {

    }
}
