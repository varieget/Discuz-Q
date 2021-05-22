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

namespace App\Api\Controller\TopicV3;

use App\Api\Controller\ThreadsV3\ThreadTrait;
use App\Api\Controller\ThreadsV3\ThreadListTrait;
use App\Common\ResponseCode;
use App\Models\Category;
use App\Models\Topic;
use App\Models\Thread;
use App\Models\ThreadTopic;
use App\Models\User;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Illuminate\Support\Arr;

class TopicListController extends DzqController
{
    use ThreadTrait;
    use ThreadListTrait;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return true;
    }

    public function main()
    {
        $filter = $this->inPut('filter');
        $currentPage = $this->inPut('page');
        $perPage = $this->inPut('perPage');

        if (Arr::has($filter, 'topicId') && Arr::get($filter, 'topicId') != 0) {
            $topicData = Topic::query()->where('id', $filter['topicId'])->first();
            if (!empty($topicData)) {
                $this->refreshTopicViewCount($topicData);
                $this->refreshTopicThreadCount($topicData);
            }
        }
        $topics = $this->filterTopics($filter, $currentPage, $perPage);
        $topicsList = $topics['pageData'];
        $topicIds = array_column($topicsList, 'id');
        $userIds = array_column($topicsList, 'user_id');
        $userDatas = User::instance()->getUsers($userIds);
        $userDatas = array_column($userDatas, null, 'id');
        $topicThreadDatas = [];

        $threads = $this->getFilterThreads($topicIds);
        $threads = $this->getFullThreadData($threads);
        foreach ($threads as $key => $value) {
            $topicThreadDatas[$value['topicId']][$value['threadId']] = $value;
        }

        if (!Arr::has($filter, 'content') && (!Arr::has($filter, 'topicId') || (Arr::has($filter, 'topicId') && Arr::get($filter, 'topicId') == 0))) {
            $topicLastThreadDatas = [];
            foreach ($topicThreadDatas as $key => $value) {
                $topicThreadIds = array_column($value, 'threadId');
                $lastThreadId = max($topicThreadIds);
                $topicLastThreadDatas[$key][$lastThreadId] = $value[$lastThreadId];
            }
            $topicThreadDatas = $topicLastThreadDatas;
        }

        $result = [];
        foreach ($topicsList as $topic) {
            $topicId = $topic['id'];
            $thread = [];
            if (isset($topicThreadDatas[$topicId])) {
                $thread = array_values($topicThreadDatas[$topicId]);
            }

            $result[] = [
                'topicId' => $topic['id'],
                'userId' => $topic['user_id'],
                'username' => $userDatas[$topic['user_id']]['username'] ?? '',
                'content' => $topic['content'],
                'viewCount' => $topic['view_count'],
                'threadCount' => $topic['thread_count'],
                'recommended' => (bool) $topic['recommended'],
                'recommendedAt' => $topic['recommended_at'] ?? '',
                'threads' => $thread
            ];
        }

        $topics['pageData'] = $result;
        return $this->outPut(ResponseCode::SUCCESS, '', $this->camelData($topics));
    }

    private function filterTopics($filter, $currentPage, $perPage)
    {
        $query = Topic::query();

        if ($username = trim(Arr::get($filter, 'username'))) {
            $query->join('users', 'users.id', '=', 'topics.user_id')
                ->where('users.username', 'like', '%' . $username . '%');
        }

        if ($content = trim(Arr::get($filter, 'content'))) {
            $query->where('topics.content', 'like', '%' . $content . '%');
        }

        if ($createdAtBegin = Arr::get($filter, 'createdAtBegin')) {
            $query->where('topics.created_at', '>=', $createdAtBegin);
        }

        if ($createdAtEnd = Arr::get($filter, 'createdAtEnd')) {
            $query->where('topics.created_at', '<=', $createdAtEnd);
        }

        if ($threadCountBegin = Arr::get($filter, 'threadCountBegin')) {
            $query->where('topics.thread_count', '>=', $threadCountBegin);
        }

        if ($threadCountEnd = Arr::get($filter, 'threadCountEnd')) {
            $query->where('topics.thread_count', '<=', $threadCountEnd);
        }

        if ($viewCountBegin = Arr::get($filter, 'viewCountBegin')) {
            $query->where('topics.view_count', '>=', $viewCountBegin);
        }

        if ($viewCountEnd = Arr::get($filter, 'viewCountEnd')) {
            $query->where('topics.view_count', '<=', $viewCountEnd);
        }

        if (Arr::has($filter, 'recommended') && Arr::get($filter, 'recommended') != '') {
            $query->where('topics.recommended', (int)Arr::get($filter, 'recommended'));
        }

        if ($topicId = trim(Arr::get($filter, 'topicId'))) {
            $query->where('topics.id', '=', $topicId);
        }

        if ((Arr::has($filter, 'hot') && Arr::get($filter, 'hot') == 1) || 
            (Arr::has($filter, 'sortBy') && Arr::get($filter, 'sortBy') == Topic::SORT_BY_VIEWCOUNT)) {
            $query->orderByDesc('topics.view_count');
        } elseif (Arr::has($filter, 'sortBy') && Arr::get($filter, 'sortBy') == Topic::SORT_BY_THREADCOUNT) {
            $query->orderByDesc('topics.thread_count');
        } else{
            $query->orderByDesc('topics.created_at');
        }

        $topics = $this->pagination($currentPage, $perPage, $query);
        return $topics;
    }

    function getFilterThreads($topicIds)
    {
        $categoryids = [];
        $categoryids = Category::instance()->getValidCategoryIds($this->user, $categoryids);
        if (!$categoryids) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '没有内容浏览权限');
        }
        $threads = $this->getThreadsBuilder($topicIds);
        !empty($categoryids) && $threads->whereIn('category_id', $categoryids);
        return $threads->get()->toArray();
    }

    private function getThreadsBuilder($topicIds)
    {
        return Thread::query()
            ->from('threads as th')
            ->join('thread_topic as tt', 'tt.thread_id', '=', 'th.id')
            ->whereNull('th.deleted_at')
            ->where('th.is_sticky', Thread::BOOL_NO)
            ->where('th.is_draft', Thread::IS_NOT_DRAFT)
            ->where('th.is_approved', Thread::APPROVED)
            ->whereIn('tt.topic_id', $topicIds)
            ->orderByDesc('th.created_at');
    }

    /**
     * refresh thread count
     * 用户删除、帖子审核、帖子逻辑删除、帖子草稿不计算
     */
    private function refreshTopicThreadCount($topicData)
    {
        $threadCount = ThreadTopic::join('threads', 'threads.id', 'thread_topic.thread_id')
            ->where('thread_topic.topic_id', $topicData->id)
            ->where('threads.is_approved', Thread::APPROVED)
            ->where('threads.is_draft', Thread::IS_NOT_DRAFT)
            ->whereNull('threads.deleted_at')
            ->whereNotNull('user_id')
            ->count();
        $topicData->thread_count = $threadCount;
        $topicData->save();
    }

    /**
     * refresh view count
     * 帖子审核、帖子逻辑删除、帖子草稿不计算
     */
    private function refreshTopicViewCount($topicData)
    {
        $viewCount = ThreadTopic::join('threads', 'threads.id', 'thread_topic.thread_id')
            ->where('thread_topic.topic_id', $topicData->id)
            ->where('threads.is_approved', Thread::APPROVED)
            ->where('threads.is_draft', Thread::IS_NOT_DRAFT)
            ->whereNull('threads.deleted_at')
            ->sum('view_count');
        $topicData->view_count = $viewCount;
        $topicData->save();
    }
}
