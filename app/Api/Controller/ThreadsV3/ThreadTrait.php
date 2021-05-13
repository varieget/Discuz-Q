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
use App\Common\CacheKey;
use App\Common\DzqCache;
use App\Common\Utils;
use App\Models\Category;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Post;
use App\Models\PostUser;
use App\Models\Thread;
use App\Models\ThreadUser;
use App\Models\User;
use App\Modules\ThreadTom\TomConfig;
use App\Modules\ThreadTom\TomTrait;
use App\Repositories\UserRepository;
use App\Settings\SettingsRepository;
use Illuminate\Support\Str;

trait ThreadTrait
{
    use TomTrait;

    public function packThreadDetail($user, $group, $thread, $post, $tomInputIndexes, $analysis = false, $tags = [])
    {
        $loginUser = $this->user;
        $userField = $this->getUserInfoField($loginUser, $user, $thread);
        $groupField = $this->getGroupInfoField($group);
        $likeRewardField = $this->getLikeRewardField($thread, $post);//列表页传参
        $payType = $this->threadPayStatus($loginUser, $thread, $paid);
        $canViewTom = $this->canViewTom($loginUser, $thread, $payType, $paid);
        $contentField = $this->getContentField($loginUser,$thread, $post, $tomInputIndexes, $payType, $paid, $canViewTom);
        $result = [
            'threadId' => $thread['id'],
            'postId' => $post['id'],
            'userId' => $thread['user_id'],
            'categoryId' => $thread['category_id'],
            'topicId' => $thread['topic_id'] ?? 0,
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
            $concatString = $thread['title'] . $post['content'];
            list($searches, $replaces) = ThreadHelper::getThreadSearchReplace($concatString);
            $result['title'] = str_replace($searches, $replaces, $result['title']);
            $result['content']['text'] = str_replace($searches, $replaces, $result['content']['text']);
        }
        return $result;
    }

    private function canViewTom($user, $thread, $payType, $paid)
    {
        //todo
        return true;
        if ($payType != Thread::PAY_FREE) {//付费贴
            $permissions = Permission::getUserPermissions($user);
            if (in_array('freeViewPosts', $permissions) || $thread['user_id'] == $user->id || $user->isAdmin() || $paid == true) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    private function getFavoriteField($threadId, $loginUser)
    {
        $userId = $loginUser->id;
        $favorites = DzqCache::extractCacheArrayData(CacheKey::LIST_THREADS_V3_POST_FAVOR, $userId);
        $favorites = $favorites[$userId] ?? [];
        if ($favorites) {
            if (array_key_exists($threadId, $favorites)) {
                if (empty($favorites[$threadId])) {
                    return false;
                } else {
                    return true;
                }
            }
        }
        return ThreadUser::query()->where(['thread_id' => $threadId, 'user_id' => $loginUser->id])->exists();
    }

    private function getCategoryNameField($categoryId)
    {
        $categories = Category::getCategories();
        $categories = array_column($categories, null, 'id');
        return $categories[$categoryId]['name'] ?? null;
    }

    /**
     * @desc 获取操作权限
     * @param User $loginUser
     * @param $thread
     * @return bool[]
     */
    private function getAbilityField(User $loginUser, $thread)
    {
        /** @var UserRepository $userRepo */
        $userRepo = app(UserRepository::class);
        /** @var SettingsRepository $settingRepo */
        $settingRepo = app(SettingsRepository::class);

        return [
            'canEdit' => $userRepo->canEditThread($loginUser, $thread),
            'canDelete' => $userRepo->canHideThread($loginUser, $thread),
            'canEssence' => $userRepo->canEssenceThread($loginUser, $thread->category_id),
            'canStick' => $userRepo->canStickThread($loginUser),
            'canReply' => $userRepo->canReplyThread($loginUser, $thread->category_id),
            'canViewPost' => $userRepo->canViewThreadDetail($loginUser, $thread),
            'canBeReward' => (bool)$settingRepo->get('site_can_reward'),
        ];
    }

    private function threadPayStatus($loginUser, $thread, &$paid)
    {
        $payType = Thread::PAY_FREE;
        $userId = $loginUser->id;
        $threadId = $thread['id'];
        $thread['price'] > 0 && $payType = Thread::PAY_THREAD;
        $thread['attachment_price'] > 0 && $payType = Thread::PAY_ATTACH;
        if ($payType == Thread::PAY_FREE) {
            $paid = null;
        } else {
            $orders = DzqCache::extractCacheArrayData(CacheKey::LIST_THREADS_V3_USER_ORDERS, $userId);
            $orders = $orders[$userId] ?? [];
            if ($orders) {
                if (array_key_exists($threadId, $orders)) {
                    if (empty($orders[$threadId])) {
                        $paid = false;
                    } else {
                        $paid = true;
                    }
                    return $payType;
                }
            }
            $paid = Order::query()
                ->where([
                    'thread_id' => $threadId,
                    'user_id' => $userId,
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
            if (in_array(TomConfig::TOM_REWARD, $tags)) {
                $obj['isReward'] = true;
            }
        }
        return $obj;
    }

    private function getContentField($loginUser,$thread, $post, $tomInput, $payType, $paid, $canViewTom)
    {
        $content = [
            'text' => null,
            'indexes' => null
        ];
        if ($payType == Thread::PAY_FREE || $loginUser->id == $thread['user_id']) {
            $content['text'] = $post['content'];
            $content['indexes'] = $this->tomDispatcher($tomInput, $this->SELECT_FUNC, $thread['id'], null, $canViewTom);
        } else {
            if ($paid) {
                $content['text'] = $post['content'];
                $content['indexes'] = $this->tomDispatcher($tomInput, $this->SELECT_FUNC, $thread['id'], null, $canViewTom);
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
        if (!empty($content['text'])) {
            $content['text'] = str_replace(['<r>', '</r>'], ['', ''], $content['text']);
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
        $ret = [
            'users' => [],
            'likePayCount' => $post['like_count'] + $thread['rewarded_count'] + $thread['paid_count'],
            'shareCount' => $thread['share_count'],
            'postCount' => $thread['post_count']
        ];
        $threadId = $thread['id'];
        $postId = $post['id'];
        $postUsers = DzqCache::extractCacheArrayData(CacheKey::LIST_THREADS_V3_POST_USERS, $threadId, function ($threadId) use ($postId, $post) {
            return ThreadHelper::getThreadLikedDetail($threadId, $postId, $post, false);
        });
        !empty($postUsers[$threadId]) && $ret['users'] = $postUsers[$threadId];
        return $ret;
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
        [$title, $content] = explode($sep, $censor->checkText($contentForCheck));
        $isApproved = $censor->isMod;
        return [$title, $content];
    }

    private function isLike($loginUser, $post)
    {
        if (empty($loginUser) || empty($post)) {
            return false;
        }
        $userId = $loginUser->id;
        $postId = $post['id'];
        $postUser = DzqCache::extractCacheArrayData(CacheKey::LIST_THREADS_V3_POST_LIKED, $userId);
        $postUser = $postUser[$userId] ?? [];
        if ($postUser) {
            if (array_key_exists($postId, $postUser)) {
                if (empty($postUser[$postId])) {
                    return false;
                } else {
                    return true;
                }
            }
        }
        return PostUser::query()->where('post_id', $post['id'])->where('user_id', $userId)->exists();
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
