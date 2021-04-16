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

use App\Common\ResponseCode;
use App\Models\GroupUser;
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadHot;
use App\Models\ThreadText;
use App\Models\ThreadTom;
use App\Models\User;
use App\Modules\ThreadTom\TomTrait;
use Carbon\Carbon;
use Discuz\Base\DzqController;

class ThreadDetailController extends DzqController
{

    use TomTrait;

    public function main()
    {
        $threadId = $this->inPut('threadId');
        $thread = Thread::getOneActiveThread($threadId);
        $post = Post::getOneActivePost($threadId);
        if (!$thread || !$post) {
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
        }
        if (!$this->canViewThreadDetail($this->user, $thread['category_id'])) {
            $this->outPut(ResponseCode::UNAUTHORIZED);
        }
        $user = User::query()->where('id', $thread['user_id'])->first();
        $groups = GroupUser::instance()->getGroupInfo([$user->id]);

        $groups = array_column($groups, null, 'user_id');

        $result = [
            'user' => ['userName' => '匿名用户'],
            'group' => null,
            'categoryId' => $thread['category_id'],
            'title' => $thread['title'],
            'position' => [
                'longitude' => $thread['longitude'],
                'latitude' => $thread['latitude'],
                'address' => $thread['address'],
                'location' => $thread['location']
            ],
            'isSticky' => $thread['is_sticky'],
            'isEssence' => $thread['is_essence'],
            'isAnonymous' => $thread['is_anonymous'],
            'isSite' => $thread['is_site'],
            'postCount' => $thread['post_count'],
            'viewCount' => $thread['view_count'],
            'rewardedCount' => $thread['rewarded_count'],
            'paidCount' => $thread['paid_count'],
            'postedAt' => $thread['posted_at'],
            'createdAt' => date('Y-m-d H:i:s', strtotime($thread['created_at'])),
            'content' => $this->getContent($thread, $post)
        ];
        if (!empty($groups[$thread['user_id']])) {
            $result['group'] = $this->getGroupInfo($groups[$thread['user_id']]);
        }
        if ((!$thread['is_anonymous'] && !empty($user)) || $this->user->id == $thread['user_id']) {
            $result['user'] = $this->getUserInfo($user);
        }
        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }

    private function getGroupInfo($group)
    {
        return [
            'id' => $group['group_id'],
            'groupName' => $group['groups']['name'],
            'groupIcon' => $group['groups']['icon'],
            'isDisplay' => $group['groups']['is_display']
        ];
    }

    private function getUserInfo($user)
    {
        return [
            'id' => $user['id'],
            'userName' => $user['username'],
            'avatar' => $user['avatar'],
            'threadCount' => $user['thread_count'],
            'followCount' => $user['follow_count'],
            'fansCount' => $user['fans_count'],
            'likedCount' => $user['liked_count'],
            'questionCount' => $user['question_count'],
            'isRealName' => !empty($user['realname']),
            'joinedAt' => date('Y-m-d H:i:s', strtotime($user['joined_at']))
        ];
    }

    private function getContent($thread, $post)
    {
        $threadId = $thread->id;
        $threadTom = ThreadTom::query()
            ->where([
                'thread_id' => $threadId,
                'status' => ThreadTom::STATUS_ACTIVE
            ])->get()->toArray();
        $tomContent = [];
        foreach ($threadTom as $item) {
            $tomContent['indexes'][$item['key']] = [
                'tomId' => $item['tom_type'],
                'operation' => $this->SELECT_FUNC,
                'body' => json_decode($item['value'], true)
            ];
        }
        $tomJsons = $this->tomDispatcher($tomContent, $this->SELECT_FUNC);
        $content = [
            'text' => $post['content'],
            'indexes' => $tomJsons
        ];
        return $content;
    }
}
