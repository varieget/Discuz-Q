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


use App\Models\Order;
use App\Models\Post;
use App\Models\PostUser;
use App\Models\Thread;
use App\Models\User;
use App\Modules\ThreadTom\TomTrait;

trait ThreadTrait
{
    use TomTrait;

    public function packThreadDetail($user, $group, $thread, $post, $tomInputIndexes, $analysis = false)
    {
        $loginUser = $this->user;
        $userField = $this->getUserInfoField($loginUser, $user, $thread);
        $groupField = $this->getGroupInfoField($group);
        $likeRewardField = $this->getLikeRewardField($thread, $post);
        $textCoverField = $this->boolTextCoverField($post);
        $contentField = $this->getContentField($textCoverField, $thread, $post, $tomInputIndexes);
        $result = [
            'threadId' => $thread['id'],
            'textCover' => $textCoverField,
            'userId' => $thread['user_id'],
            'categoryId' => $thread['category_id'],
            'title' => $thread['title'],
            'viewCount' => $thread['view_count'],
            'price' => $thread['price'],
            'attachmentPrice' => $thread['attachment_price'],
            'isEssence' => $thread['is_essence'],
            'user' => $userField,
            'group' => $groupField,
            'likeReward' => $likeRewardField,
            'position' => [
                'longitude' => $thread['longitude'],
                'latitude' => $thread['latitude'],
                'address' => $thread['address'],
                'location' => $thread['location']
            ],
            'content' => $contentField
        ];
        if ($analysis) {
            $s = $thread['title'] . $post['content'];
            list($search, $replace) = Thread::instance()->getReplaceString($s);
            $result['title'] = str_replace($search, $replace, $result['title']);
            $result['content']['text'] = str_replace($search, $replace, $result['content']['text']);
        }
        return $result;
    }

    private function getContentField($textCover, $thread, $post, $tomInput)
    {
        $content = [
            'text' => $textCover ? $post['content'] : Post::instance()->getContentSummary($post),
            'indexes' => null
        ];
        if (!empty($tomInput)) {
            $content['indexes'] = $this->tomDispatcher($tomInput, $this->SELECT_FUNC, $thread['id']);
        }
        return $content;
    }

    private function getGroupInfoField($group)
    {
        $groupResult = [];
        if (!empty($group)) {
            $groupResult = [
                'groupId' => $group['group_id'],
                'groupName' => $group['groups']['name'],
                'groupIcon' => $group['groups']['icon'],
                'isDisplay' => $group['groups']['is_display']
            ];
        }
        return $groupResult;
    }

    private function boolTextCoverField($post)
    {
        $textCover = false;
        if (mb_strlen($post['content']) >= 200) {
            $textCover = true;
        }
        return $textCover;
    }

    private function getUserInfoField($loginUser, $user, $thread)
    {
        $userResult = [
            'userName' => '匿名用户'
        ];
        //非匿名用户
        if ((!$thread['is_anonymous'] && !empty($user)) || $loginUser->id == $thread['user_id']) {
            $userResult = [
                'userId' => $user['id'],
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
        return $userResult;
    }

    private function getLikeRewardField($thread, $post)
    {
        $threadId = $thread['id'];
        $postId = $post['id'];
        $postUser = PostUser::query()->where('post_id', $postId)->orderByDesc('created_at');
        $orderUser = Order::query()->where(['thread_id' => $threadId, 'status' => Order::ORDER_STATUS_PAID])->orderByDesc('created_at');
        $postUser = $postUser->select('user_id', 'created_at')->limit(2)->get()->toArray();
        $orderUser = $orderUser->select('user_id', 'created_at')->limit(2)->get()->toArray();
        $mUser = array_merge($postUser, $orderUser);
        usort($mUser, function ($a, $b) {
            return strtotime($a['created_at']) < strtotime($b['created_at']);
        });
        $mUser = array_slice($mUser, 0, 2);
        $userIds = array_column($mUser, 'user_id');
        $users = [];
        $usersObj = User::query()->whereIn('id', $userIds)->get();
        foreach ($usersObj as $item) {
            $users[] = [
                'userId' => $item->id,
                'avatar' => $item->avatar,
                'userName' => $item->username
            ];
        }
        return [
            'users' => $users,
            'likePayCount' => $post['like_count'] + $thread['rewarded_count'] + $thread['paid_count'],
            'shareCount' => $thread['share_count']
        ];
    }

}
