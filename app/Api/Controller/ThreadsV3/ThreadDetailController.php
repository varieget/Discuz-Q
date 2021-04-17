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
use App\Models\Order;
use App\Models\Post;
use App\Models\PostUser;
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
            'likeReward' => $this->getLikeReward($thread, $post),
            'threadId' => $threadId,
            'categoryId' => $thread['category_id'],
            'title' => $thread['title'],
            'position' => [
                'longitude' => $thread['longitude'],
                'latitude' => $thread['latitude'],
                'address' => $thread['address'],
                'location' => $thread['location']
            ],
            'price' => $thread['price'],
            'attachmentPrice' => $thread['attachment_price'],
            'isSticky' => $thread['is_sticky'],
            'isEssence' => $thread['is_essence'],
            'postCount' => $thread['post_count'],
            'viewCount' => $thread['view_count'],
            'rewardedCount' => $thread['rewarded_count'],
            'paidCount' => $thread['paid_count'],
            'shareCount' => $thread['share_count'],
            'postedAt' => $thread['posted_at'],
            'createdAt' => date('Y-m-d H:i:s', strtotime($thread['created_at'])),
            'content' => $this->getContent($thread, $post)
        ];
        $linkString = '';
        if (!empty($groups[$thread['user_id']])) {
            $result['group'] = $this->getGroupInfo($groups[$thread['user_id']]);
        }
        if ((!$thread['is_anonymous'] && !empty($user)) || $this->user->id == $thread['user_id']) {
            $result['user'] = $this->getUserInfo($user);
        }
        $linkString = $result['title'] . $result['content']['text'];
        list($search, $replace) = Thread::instance()->getReplaceString($linkString);
        $result['title'] = str_replace($search, $replace, $result['title']);
        $result['content']['text'] = str_replace($search, $replace, $result['content']['text']);
        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }

    private function getLikeReward($thread, $post)
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
            ])->orderBy('key')->get()->toArray();
        $tomContent = [];
        foreach ($threadTom as $item) {
            $tomContent['indexes'][$item['key']] = $this->buildTomJson($threadId,$item['tom_type'],$this->SELECT_FUNC,json_decode($item['value'], true));
        }
        $tomJsons = $this->tomDispatcher($tomContent, $this->SELECT_FUNC,$threadId);
        $content = [
            'text' => $post['content'],
            'indexes' => $tomJsons
        ];
        return $content;
    }
}
