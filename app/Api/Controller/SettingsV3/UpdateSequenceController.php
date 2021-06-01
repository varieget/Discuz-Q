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

namespace App\Api\Controller\SettingsV3;

use App\Common\CacheKey;
use App\Models\Sequence;
use App\Common\ResponseCode;
use App\Models\Thread;
use App\Models\User;
use App\Repositories\UserRepository;
use Discuz\Contracts\Setting\SettingsRepository;
use Discuz\Base\DzqController;

class UpdateSequenceController extends DzqController
{
    protected $cache;

    public function __construct(User $actor, SettingsRepository $settings)
    {
        $this->cache = app('cache');
        $this->actor = $actor;
        $this->settings = $settings;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return $this->user->isAdmin();
    }

    public function main()
    {
        $cacheKey = CacheKey::LIST_SEQUENCE_THREAD_INDEX;
        $block_thread_ids = $this->inPut('blockThreadIds');
        $block_topic_ids = $this->inPut('blockTopicIds');
        $block_user_ids = $this->inPut('blockUserIds');
        $category_ids = $this->inPut('categoryIds');
        $group_ids = $this->inPut('groupIds');
        $site_open_sort = $this->inPut('siteOpenSort');
        $thread_ids = $this->inPut('threadIds');
        $topic_ids = $this->inPut('topicIds');
        $user_ids = $this->inPut('userIds');

        $attributes = [
            'site_open_sort'=>$site_open_sort,
            'category_ids'=>$category_ids,
            'group_ids'=>$group_ids,
            'user_ids'=>$user_ids,
            'topic_ids'=>$topic_ids,
            'thread_ids'=>$thread_ids,
            'block_user_ids'=>$block_user_ids,
            'block_topic_ids'=>$block_topic_ids,
            'block_thread_ids'=>$block_thread_ids,
        ];
        if(!isset($attributes['site_open_sort']) || empty($attributes['site_open_sort'])){
            $attributes['site_open_sort'] = 0;
        }

        $this->settings->set('site_open_sort', $attributes['site_open_sort'], 'default');

        $sequence = array(
            'category_ids' => $attributes['category_ids'] ?? '',
            'group_ids' => $attributes['group_ids'] ?? '',
            'user_ids' => $attributes['user_ids'] ?? '',
            'topic_ids' => $attributes['topic_ids'] ?? '',
            'thread_ids' => $attributes['thread_ids'] ?? '',
            'block_user_ids' => $attributes['block_user_ids'] ?? '',
            'block_topic_ids' => $attributes['block_topic_ids'] ?? '',
            'block_thread_ids' => $attributes['block_thread_ids'] ?? ''
        );

        Sequence::query()->delete();
        Sequence::query()->insert($sequence);

        if($attributes['site_open_sort'] == 0){
            $index_thread_ids = array();
            $this->cache->put($cacheKey, $index_thread_ids, 1800);
            $this->appendCache(CacheKey::LIST_SEQUENCE_THREAD_INDEX_KEYS, $cacheKey, 1800);
            return $this->outPut(ResponseCode::SUCCESS);
        }

        $query = Thread::query();

        if(!empty($attributes['category_ids'])){
            $query->orWhereIn('threads.category_id', explode(',', $attributes['category_ids']));
        }

        if(!empty($attributes['user_ids'])){
            $query->orWhereIn('threads.user_id', explode(',', $attributes['user_ids']));
        }

        if(!empty($attributes['group_ids'])){
            $query->leftJoin('group_user', 'threads.user_id', '=', 'group_user.user_id');
            $query->orWhereIn('group_user.group_id', explode(',', $attributes['group_ids']));
        }

        if(!empty($attributes['topic_ids'])){
            $query->leftJoin('thread_topic', 'threads.id', '=', 'thread_topic.thread_id');
            $query->orWhereIn('thread_topic.topic_id', explode(',', $attributes['topic_ids']));
        }

        if(!empty($attributes['thread_ids'])) {
            $query->orWhereIn('threads.id', explode(',', $attributes['thread_ids']));
        }

        if(!empty($attributes['block_user_ids'])) {
            $query->whereNotIn('threads.user_id', explode(',', $attributes['block_user_ids']));
        }

        if(!empty($attributes['block_thread_ids'])) {
            $query->whereNotIn('threads.id', explode(',', $attributes['block_thread_ids']));
        }

        if(!empty($attributes['block_topic_ids'])) {
            $query->whereNotIn('thread_topic.topic_id', explode(',', $attributes['block_topic_ids']));
        }

        $query->where('threads.is_approved', 1);
        $query->where('threads.is_draft', 0);
        $query->whereNull('threads.deleted_at');
        $query->whereNotNull('threads.user_id');
        $query->orderBy('threads.created_at', 'desc');
        $index_thread_ids['thread_count'] = $query->count();
        $index_thread_ids['ids'] = $query->limit(20)->offset(0)->pluck('id')->toArray();
        $this->cache->put($cacheKey, $index_thread_ids, 1800);
        $this->appendCache(CacheKey::LIST_SEQUENCE_THREAD_INDEX_KEYS, $cacheKey, 1800);
        return $this->outPut(ResponseCode::SUCCESS);
    }

    /**
     *缓存key单独记录便于数据变更的时候清除缓存
     */
    private function appendCache($key, $value, $ttl)
    {
        $v0 = $this->cache->get($key);
        if (empty($v0)) {
            $v1 = [$value];
        } else {
            $v1 = json_decode($v0, true);
            if (!empty($v1)) {
                $v1[] = $value;
            } else {
                return false;
            }
        }
        $v1 = array_unique($v1);
        $this->cache->put($key, json_encode($v1, 256), $ttl);
        return $v1;
    }
}
