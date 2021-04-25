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


use App\Censor\Censor;
use App\Common\Utils;
use App\Models\Category;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Post;
use App\Models\PostUser;
use App\Models\Thread;
use App\Models\ThreadUser;
use App\Models\User;
use App\Modules\ThreadTom\PreQuery;
use App\Modules\ThreadTom\TomConfig;
use App\Modules\ThreadTom\TomTrait;
use Illuminate\Support\Str;

trait ThreadTrait
{
    use TomTrait;

    public function packThreadDetail($user, $group, $thread, $post, $tomInputIndexes, $analysis = false, $tags = [])
    {
        $loginUser = $this->user;
        $userField = $this->getUserInfoField($loginUser, $user, $thread);
        $groupField = $this->getGroupInfoField($group);
        $likeRewardField = $this->getLikeRewardField($thread, $post);
        $payType = $this->threadPayStatus($thread, $paid);
        $contentField = $this->getContentField($thread, $post, $tomInputIndexes, $payType, $paid);
        $result = [
            'threadId' => $thread['id'],
            'postId' => $post['id'],
            'userId' => $thread['user_id'],
            'categoryId' => $thread['category_id'],
            'categoryName' => $this->getCategoryNameField($thread['category_id']),
            'title' => $thread['title'],
            'viewCount' => empty($thread['view_count']) ? 0 : $thread['view_count'],
            'postCount' => $thread['post_count'] - 1,
            'isApproved' => $thread['is_approved'],
            'isStick' => $thread['is_sticky'],
            'isFavorite' => $this->getFavoriteField($thread['id'], $loginUser),
            'price' => floatval($thread['price']),
            'attachmentPrice' => floatval($thread['attachment_price']),
            'payType' => $payType,
            'paid' => $paid,
            'isLike' => $this->isLike($loginUser, $post),
            'createdAt' => date('Y-m-d H:i:s', strtotime($thread['created_at'])),
            'diffTime' => Utils::diffTime($thread['created_at']),
            'user' => $userField,
            'group' => $groupField,
            'likeReward' => $likeRewardField,
            'displayTag' => $this->getDisplayTagField($thread, $tags),
            'position' => [
                'longitude' => $thread['longitude'],
                'latitude' => $thread['latitude'],
                'address' => $thread['address'],
                'location' => $thread['location']
            ],
            'ability' => $this->getAbilityField($loginUser, $thread),
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

    private function getFavoriteField($threadId, $loginUser)
    {
        if (app()->has(PreQuery::THREAD_LIST_FAVORITE)) {
            $favorites = app()->get(PreQuery::THREAD_LIST_FAVORITE);
            if (isset($favorites[$threadId])) {
                return true;
            }
        } else {
            return ThreadUser::query()->where(['thread_id' => $threadId, 'user_id' => $loginUser->id])->exists();
        }
        return false;
    }

    private function getCategoryNameField($categoryId)
    {
        if (app()->has(PreQuery::THREAD_LIST_CATEGORIES)) {
            $categories = app()->get(PreQuery::THREAD_LIST_CATEGORIES);
        } else {
            $categories = Category::getCategories();
        }
        $categoryName = '';
        foreach ($categories as $category) {
            if ($category['id'] == $categoryId) {
                $categoryName = $category['name'];
            }
        }
        return $categoryName;
    }

    /**
     * @desc 获取操作权限
     * @param $loginUser
     * @param $thread
     * @return bool[]
     */
    private function getAbilityField($loginUser, $thread)
    {

        $data = [
            'canEdit' => true,
            'canDelete' => true,
            'canEssence' => true,
            'canStick' => true,
            'canReply' => true,
            'canViewPost' => true
        ];

        if ($loginUser->isAdmin()) {
            return $data;
        }

        $permission = array_flip(Permission::getUserPermissions($loginUser));

        if (!isset($permission['thread.editOwnThreadOrPost']) && !isset($permission["category{$thread['category_id']}.thread.editOwnThreadOrPost"])) {
            $data['canEdit'] = false;
        }
        if (!isset($permission['thread.hideOwnThreadOrPost']) && !isset($permission["category{$thread['category_id']}.thread.hideOwnThreadOrPost"])) {
            $data['canDelete'] = false;
        }
        if (!isset($permission['thread.essence']) && !isset($permission["category{$thread['category_id']}.thread.essence"])) {
            $data['canEssence'] = false;
        }
        if (!isset($permission['thread.sticky'])) {
            $data['canStick'] = false;
        }
        if (!isset($permission['thread.reply']) && !isset($permission["category{$thread['category_id']}.thread.reply"])) {
            $data['canReply'] = false;
        }
        if (!isset($permission['thread.viewPosts']) && !isset($permission["category{$thread['category_id']}.thread.viewPosts"])) {
            $data['canViewPost'] = false;
        }

        return $data;
    }

    private function threadPayStatus($thread, &$paid)
    {
        $payType = Thread::PAY_FREE;
        $thread['price'] > 0 && $payType = Thread::PAY_THREAD;
        $thread['attachment_price'] > 0 && $payType = Thread::PAY_ATTACH;
        if ($payType == Thread::PAY_FREE) {
            $paid = null;
        } else {
            $paid = Order::query()
                ->where([
                    'thread_id' => $thread['id'],
                    'user_id' => $this->user->id,
                    'status' => Order::ORDER_STATUS_PAID
                ])->whereIn('type', [Order::ORDER_TYPE_THREAD, Order::ORDER_TYPE_ATTACHMENT])->exists();
        }
        return $payType;
    }

    /**
     * @desc 显示在帖子上的标签，目前支持 付费/精华/红包/悬赏 四种
     * @param $thread
     * @param $tags
     * @return bool[]
     */
    private function getDisplayTagField($thread, $tags)
    {
        $obj = [
            'isPrice' => false,
            'isEssence' => false,
            'isRedPack' => null,
            'isReward' => null
        ];
        if ($thread['price'] > 0 || $thread['attachment_price'] > 0) {
            $obj['isPrice'] = true;
        }
        if ($thread['is_essence']) {
            $obj['isEssence'] = true;
        }
        $tags = array_column($tags, 'tag');
        if (!empty($tags)) {
            if (in_array(TomConfig::TOM_REDPACK, $tags)) {
                $obj['isRedPack'] = true;
            }
            if (in_array(TomConfig::TOM_DOC, $tags)) {
                $obj['isReward'] = true;
            }
        }
        return $obj;
    }

    private function getContentField($thread, $post, $tomInput, $payType, $paid)
    {
        $content = [
            'text' => null,
            'indexes' => null
        ];
        if ($payType == Thread::PAY_FREE) {
            $content['text'] = $post['content'];
            $content['indexes'] = $this->tomDispatcher($tomInput, $this->SELECT_FUNC, $thread['id']);
        } else {
            if ($paid) {
                $content['text'] = $post['content'];
                $content['indexes'] = $this->tomDispatcher($tomInput, $this->SELECT_FUNC, $thread['id']);
            } else {
                $freeWords = $thread['free_words'];
                if (empty($freeWords)) {
                    $text = $post['content'];
                } else {
                    $text = strip_tags($post['content']);
                    $freeLength = mb_strlen($text) * $freeWords;
                    $text = mb_substr($text, 0, $freeLength) . Post::SUMMARY_END_WITH;
                }
                $content['text'] = $text;
            }
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

    private function getUserInfoField($loginUser, $user, $thread)
    {
        $userResult = [
            'userName' => '匿名用户'
        ];
        //非匿名用户
        if ((!$thread['is_anonymous'] && !empty($user)) || $loginUser->id == $thread['user_id']) {
            $userResult = [
                'userId' => $user['id'],
                'userName' => empty($user['nickname']) ? $user['username'] : $user['nickname'],
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
            'shareCount' => $thread['share_count'],
            'postCount' => $thread['post_count']
        ];
    }

    /**
     * @desc 查询是否需要审核
     * @param $title
     * @param $text
     * @param null $isApproved 是否进审核
     * @return array
     */
    private function boolApproved($title, $text, &$isApproved = null)
    {
        $censor = app(Censor::class);
        $sep = '__' . Str::random(6) . '__';
        $contentForCheck = $title . $sep . $text;
        $censor->checkText($contentForCheck);
        list($title, $content) = explode($sep, $censor->checkText($contentForCheck));
        $isApproved = $censor->isMod;
        return [$title, $content];
    }

    private function isLike($loginUser, $post)
    {
        if (empty($loginUser) || empty($post)) {
            return false;
        }
        return PostUser::query()->where('post_id', $post['id'])->where('user_id', $loginUser->id)->exists();
    }

    /*
     * @desc 前端新编辑器只能上传完整url的emoji
     * 后端需要将其解析出代号进行存储
     * @param $text
     */
    private function optimizeEmoji($text)
    {
        if ($text != strip_tags($text)) {
            $text = '<r>' . $text . '</r>';
        }
        preg_match_all('/<img.*?emoji\/qq.*?>/i', $text, $m1);
        $searches = $m1[0];
        $replaces = [];
        foreach ($searches as $search) {
            preg_match('/:[a-z]+?:/i', $search, $m2);
            $emoji = $m2[0];
            $replaces[] = $emoji;
        }
        $text = str_replace($searches, $replaces, $text);
        return $text;
    }

}