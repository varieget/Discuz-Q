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

namespace App\Api\Serializer;

use App\Models\Sequence;
use App\Models\Category;
use App\Models\Group;
use App\Models\User;
use App\Models\Topic;
use App\Models\Thread;
use Discuz\Api\Serializer\AbstractSerializer;
use Tobscure\JsonApi\Relationship;
use Illuminate\Database\Eloquent\Builder;

class SequenceSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'sequence';

    /**
     * @param Sequence $model
     * @return array
     */
    protected function getDefaultAttributes($model)
    {
        return [
            'category_ids'      => $model->category_ids,
            'group_ids'         => $model->group_ids,
            'users'          => $this->usersList($model->user_ids),
            'topics'         => $this->topicsList($model->topic_ids),
            'threads'        => $model->thread_ids,
            'block_users'    => $this->usersList($model->block_user_ids),
            'block_topics'   => $this->topicsList($model->block_topic_ids),
            'block_threads'  => $model->block_thread_ids
        ];
    }

    /**
     * @param $categoriesList
     * @return array
     */
    public function categoriesList($category_ids)
    {
        return Category::query()->whereIn('id', explode(',', $category_ids))->orderBy('sort')->get();
    }

    /**
     * @param $groupsList
     * @return array
     */
    public function groupsList($group_ids)
    {
        return Group::query()->whereIn('id', explode(',', $group_ids))->orderBy('id')->get();
    }

    /**
     * @param $usersList
     * @return array
     */
    public function usersList($user_ids)
    {
        return User::query()->whereIn('id', explode(',', $user_ids))->orderBy('id')->get();
    }

    /**
     * @param $topicsList
     * @return array
     */
    public function topicsList($topic_ids)
    {
        return Topic::query()->whereIn('id', explode(',', $topic_ids))->orderBy('id')->get();
    }

    /**
     * @param $threadsList
     * @return array
     */
    public function threadsList($thread_ids)
    {
        return Thread::query()->whereIn('id', explode(',', $thread_ids))->orderBy('id')->get();
    }
}
