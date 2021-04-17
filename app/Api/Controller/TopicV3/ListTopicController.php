<?php

/**
 * Copyright (C) 2020 Tencent Cloud.
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

use App\Common\ResponseCode;
use App\Models\Topic;
use App\Models\User;
use App\Repositories\TopicRepository;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Illuminate\Support\Arr;

class ListTopicController extends DzqController
{

    protected $topics;

    public function __construct(TopicRepository $topics,  UserRepository $user)
    {
        $this->topics = $topics;
        $this->users = $user;
    }


    public function main()
    {
        $filter = $this->inPut('filter');
        $currentPage = $this->inPut('page');
        $perPage = $this->inPut('perPage');
        $topics = $this->filterTopics($filter, $currentPage, $perPage);
        $topicsList = $topics['pageData'];

        $userIds = array_unique(array_column($topicsList, 'user_id'));
        $users = User::instance()->getUsers($userIds);
        $users = array_column($users, null, 'id');

        foreach ($topicsList as $topic) {
            $userId = $topic['user_id'];
            $user = [];
            if (!empty($users[$userId])) {
                $user = $this->getUserInfo($users[$userId]);
            }
            $result[] = [
                'topic' =>$topic,
                'user' => $user,

            ];

        }
        $topics['pageData'] = $result;
//
        return $this->outPut(ResponseCode::SUCCESS,'',$this->camelData($topics));
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

        $query->orderByDesc('created_at');
        $topics = $this->pagination($currentPage, $perPage, $query);
        return $topics;
    }


    private function getUserInfo($user)
    {
        return [
            'id' => $user['id'],
            'userName' => $user['username'],
            'avatarUrl' => $user['avatar_url'],
            'isReal' => $user['is_real'],
            'threadCount' => $user['thread_count'],
            'followCount' => $user['follow_count'],
            'fansCount' => $user['fans_count'],
            'likedCount' => $user['liked_count'],
            'signature' => $user['signature'],
            'usernameBout' => $user['username_bout'],
            'follow' => $user['follow'],
            'status' => $user['status'],
            'loginAt' => $user['login_at'],
            'joinedAt   ' => $user['joined_at'],
            'expiredAt' => $user['expired_at'],
            'createdAt' => $user['created_at'],
            'updatedAt' => $user['updated_at'],
            'canEdit' => $user['can_edit'],
            'canDelete' => $user['can_delete'],
            'showGroups' => $user['show_groups'],
            'registerReason' => $user['register_reason'],
            'banReason' => $user['ban_reason']
        ];
    }

}
