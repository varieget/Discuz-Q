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

namespace App\Api\Controller\Threads;

use App\Api\Serializer\AttachmentSerializer;
use App\Common\CacheKey;
use App\Common\ResponseCode;
use App\Common\Utils;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Emoji;
use App\Models\GroupUser;
use App\Models\Permission;
use App\Models\Post;
use App\Models\PostGoods;
use App\Models\PostUser;
use App\Models\Question;
use App\Models\Sequence;
use App\Models\Thread;
use App\Models\ThreadReward;
use App\Models\ThreadVideo;
use App\Models\Topic;
use App\Models\User;
use Discuz\Base\DzqController;

class ListThreadsV2Controller extends DzqController
{
    public function main()
    {
        $filter = $this->inPut('filter');
        $currentPage = $this->inPut('page');
        $perPage = $this->inPut('perPage');
        $homeSequence = $this->inPut('homeSequence');//默认首页
        $cache = app('cache');
        $key = md5(json_encode($filter) . $perPage . $homeSequence);
        $currentPage == 1 && $this->getCache($cache,$key);
        $serializer = $this->app->make(AttachmentSerializer::class);
        $groups = $this->user->groups->toArray();
        $groupIds = array_column($groups, 'id');
        $permissions = Permission::categoryPermissions($groupIds);
        if ($homeSequence) {
            $threads = $this->getDefaultHomeThreads($currentPage, $perPage);
        } else {
            $threads = $this->getFilterThreads($filter, $currentPage, $perPage);
        }
        $threadList = $threads['pageData'];
        !$threads && $threadList = [];
        $userIds = array_unique(array_column($threadList, 'user_id'));
        $groups = GroupUser::instance()->getGroupInfo($userIds);
        $groups = array_column($groups, null, 'user_id');
        $users = User::instance()->getUsers($userIds);
        $users = array_column($users, null, 'id');
        $threadIds = array_column($threadList, 'id');
        $posts = Post::instance()->getPosts($threadIds);
        $postsByThreadId = array_column($posts, null, 'thread_id');
        $postIds = array_column($posts, 'id');
        $likedPostIds = PostUser::instance()->getPostIdsByUid($postIds,$this->user->id);
        $attachments = Attachment::instance()->getAttachments($postIds, [Attachment::TYPE_OF_FILE, Attachment::TYPE_OF_IMAGE]);
        $attachmentsByPostId = Utils::pluckArray($attachments, 'type_id');
        $threadRewards = ThreadReward::instance()->getRewards($threadIds);
        $result = [];
        $str = '';
        foreach ($threadList as $thread) {
            $userId = $thread['user_id'];
            $user = null;
            if (!$thread['is_anonymous'] && !empty($users[$userId])) {
                $user = $this->getUserInfo($users[$userId]);
            }
            $group = [];
            if (!empty($groups[$userId])) {
                $group = $this->getGroupInfo($groups[$userId]);
            }
            $attachments = [];
            $post = null;
            if (!empty($postsByThreadId[$thread['id']])) {
                !empty($postsByThreadId[$thread['id']]) && $post = $postsByThreadId[$thread['id']];
                if(!empty($post['id']) && !empty($attachmentsByPostId[$post['id']])){
                    $attachments = $attachmentsByPostId[$post['id']];
                }
            }
            $thread = $this->getThread($thread,$post,$likedPostIds, $permissions);
            $str .= $thread['summary'];
            $rewards = null;
            if(isset($threadRewards[$thread['pid']])){
                $rewards = $threadRewards[$thread['pid']];
            }
            $result[] = [
                'user' => $user,
                'group' => $group,
                'rewards'=>$rewards,
                'thread' => $thread,
                'attachment' => $this->getAttachment($attachments, $serializer),
            ];
        }
        list($search, $replace) = $this->getReplaceString($str);
        foreach ($result as &$item) {
            $thread = $item['thread'];
            $item['thread']['summary'] = str_replace($search, $replace, $thread['summary']);
        }
        $threads['pageData'] = $result;
        $currentPage == 1 && $this->putCache($cache, $key, $threads);
        $this->outPut(ResponseCode::SUCCESS, '', $threads);
    }

    /**
     * @desc 获取本次查询要替换的特殊符号
     * @param $str
     * @return array[]
     */
    private function getReplaceString($str)
    {
        preg_match_all('/:[a-z]+:/i', $str, $m1);
        preg_match_all('/@.+? /', $str, $m2);
        preg_match_all('/#.+?#/', $str, $m3);
        $m1 = array_unique($m1[0]);
        $m2 = array_unique($m2[0]);
        $m3 = array_unique($m3[0]);
        $m2 = str_replace(['@', ''], '', $m2);
        $m3 = str_replace('#', '', $m3);
        $search = [];
        $replace = [];
        $emojis = Emoji::query()->select('code', 'url')->whereIn('code', $m1)->get()->map(function ($item) use ($search) {
            $item['url'] = Utils::getDzqDomain() . '/' . $item['url'];
            $item['html'] = sprintf('<img style="display:inline-block;vertical-align:top" src="%s" alt="ciya" class="qq-emotion">', $item['url']);
            return $item;
        })->toArray();
        $ats = User::query()->select('id', 'username')->whereIn('username', $m2)->get()->map(function ($item) {
            $item['username'] = '@' . $item['username'];
            $item['html'] = sprintf('<span id="member" value="%s">%s</span>', $item['id'], $item['username']);
            return $item;
        })->toArray();
        $topics = Topic::query()->select('id', 'content')->whereIn('content', $m3)->get()->map(function ($item) {
            $item['content'] = '#' . $item['content'] . '#';
            $item['html'] = sprintf('<span id="topic" value="%s">%s</span>', $item['id'], $item['content']);
            return $item;
        })->toArray();
        foreach ($emojis as $emoji) {
            $search[] = $emoji['code'];
            $replace[] = $emoji['html'];
        }
        foreach ($ats as $at) {
            $search[] = $at['username'];
            $replace[] = $at['html'];
        }
        foreach ($topics as $topic) {
            $search[] = $topic['content'];
            $replace[] = $topic['html'];
        }
        return [$search, $replace];
    }

    private function getThread($thread,$firstPost,$likedPostIds, $permissions)
    {
        $data = [
            'pid' => $thread['id'],
            'type' => $thread['type'],
            'categoryId' => $thread['category_id'],
            'title' => $thread['title'],
            'summary' => '',
            'price' => $thread['price'],
            'attachmentPrice' => $thread['attachment_price'],
            'postCount' => $thread['post_count'] - 1,
            'viewCount' => $thread['view_count'],
            'rewardedCount' => $thread['rewarded_count'],
            'paidCount' => $thread['paid_count'],
            'longitude' => $thread['longitude'],
            'latitude' => $thread['latitude'],
            'address' => $thread['address'],
            'location' => $thread['location'],
            'isEssence' => $thread['is_essence'],
            'createdAt' => date('Y-m-d H:i:s', strtotime($thread['created_at'])),
            'diffCreatedAt' => Utils::diffTime($thread['created_at']),
            'isRedPacket' => $thread['is_red_packet'],
            'canViewPost' => $this->canViewPosts($thread, $permissions),
            'canLike' =>true,
            'isLiked'=>false,
            'likedCount'=>0,
            'firstPostId'=>null,
            'replyCount'=>0,
            'extension' => null
        ];
        //点赞相关属性
        if (!empty($firstPost)) {
            $data['canLike'] = $this->canLikeThread($permissions);
            $data['isLiked'] = in_array($firstPost['id'], $likedPostIds) ? true : false;
            $data['likedCount'] = $firstPost['like_count'];
            $data['firstPostId'] = $firstPost['id'];
            $data['replyCount'] = $firstPost['reply_count'];
        }
        switch ($thread['type']) {
            case Thread::TYPE_OF_IMAGE:
            case Thread::TYPE_OF_AUDIO:
            case Thread::TYPE_OF_TEXT:
                $data['title'] = Post::instance()->getContentSummary($thread['id']);
                break;
            case Thread::TYPE_OF_VIDEO:
                $data['title'] = Post::instance()->getContentSummary($thread['id']);
                $data['extension'] = [
                    Thread::EXT_VIDEO => ThreadVideo::instance()->getThreadVideo($thread['id'])
                ];
                break;
            case Thread::TYPE_OF_GOODS:
                $postId = true;
                $data['title'] = Post::instance()->getContentSummary($thread['id'], $postId);;
                $data['extension'] = [
                    Thread::EXT_GOODS => PostGoods::instance()->getPostGoods($postId)
                ];
                break;
            case Thread::TYPE_OF_QUESTION:
                $data['title'] = Post::instance()->getContentSummary($thread['id']);
                $data['extension'] = [
                    Thread::EXT_QA => Question::instance()->getQuestions($thread['id'])
                ];
                break;
            default:
                break;
        }
        $data['summary'] = $data['title'];
        return $data;
    }

    private function canViewPosts($thread, $permissions)
    {
        $canViewPost = true;
        if (!$this->user->isAdmin()) {
            $viewPostStr = 'category' . $thread['category_id'] . '.thread.viewPosts';
            !in_array($viewPostStr, $permissions) && $canViewPost = false;
        }
        if ($this->user->id == $thread['user_id']) {
            $canViewPost = true;
        }
        return $canViewPost;
    }

    private function canLikeThread($permissions){
        $permission = 'thread.likePosts';
        return in_array($permission,$permissions);
    }

    private function getAttachment($attachments, $serializer)
    {
        $result = [];
        foreach ($attachments as $attachment) {
//            $result[] = Attachment::getBeautyAttachment($attachment);
            $result[] = $this->camelData($serializer->getDefaultAttributes($attachment, $this->user));
        }
        return $result;
    }

    private function getGroupInfo($group)
    {
        return [
            'pid' => $group['group_id'],
            'groupName' => $group['groups']['name'],
            'groupIcon' => $group['groups']['icon']
        ];
    }

    private function getUserInfo($user)
    {
        return [
            'pid' => $user['id'],
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

    /**
     * @desc 获取默认排序首页数据
     * @param $currentPage
     * @param $perPage
     * @return array|bool
     */
    private function getDefaultHomeThreads($currentPage, $perPage)
    {
        $sequence = Sequence::query()->first();
        if (empty($sequence)) return false;
        !empty($sequence['category_ids']) && $categoryIds = explode(',', $sequence['category_ids']);
        !empty($sequence['group_ids']) && $groupIds = explode(',', $sequence['group_ids']);
        !empty($sequence['user_ids']) && $userIds = explode(',', $sequence['user_ids']);
        !empty($sequence['topic_ids']) && $topicIds = explode(',', $sequence['topic_ids']);
        !empty($sequence['thread_ids']) && $threadIds = explode(',', $sequence['thread_ids']);
        !empty($sequence['block_user_ids']) && $blockUserIds = explode(',', $sequence['block_user_ids']);
        !empty($sequence['block_topic_ids']) && $blockTopicIds = explode(',', $sequence['block_topic_ids']);
        !empty($sequence['block_thread_ids']) && $blockThreadIds = explode(',', $sequence['block_thread_ids']);
        $threads = Thread::query()
            ->from('threads as th1')
            ->whereNull('th1.deleted_at')
            ->where('th1.is_approved', Thread::APPROVED)
            ->where('th1.is_draft', Thread::IS_NOT_DRAFT);
        if (!empty($categoryIds)) {
            $threads = $threads->whereIn('th1.category_id', $categoryIds);
        }
        if (!empty($groupIds)) {
            $threads = $threads
                ->leftJoin('group_user as g1', 'g1.user_id', '=', 'th1.user_id')
                ->whereIn('g1.group_id', $groupIds);
        }
        if (!empty($topicIds)) {
            $threads = $threads
                ->leftJoin('thread_topic as tp1', 'tp1.thread_id', '=', 'th1.id')
                ->whereIn('tp1.topic_id', $topicIds);
        }
        if (!empty($userIds)) {
            $threads = $threads->whereIn('th1.user_id', $userIds);
        }
        if (!empty($threadIds)) {
            $threads = $threads->whereIn('th1.id', $threadIds);
        }

        if (!empty($blockUserIds)) {
            $threads->whereNotExists(function ($query) use ($blockUserIds) {
                $query->whereIn('th1.user_id', $blockUserIds);
            });
        }
        if (!empty($blockThreadIds)) {
            $threads->whereNotExists(function ($query) use ($blockThreadIds) {
                $query->whereIn('th1.id', $blockThreadIds);
            });
        }
        if (!empty($blockTopicIds)) {
            $threads->whereNotExists(function ($query) use ($blockTopicIds) {
                $query->whereIn('tp1.topic_id', $blockTopicIds);
            });
        }
        return $this->pagination($currentPage, $perPage, $threads);
    }

    private function getFilterThreads($filter, $currentPage, $perPage)
    {
        if (empty($filter)) $filter = [];
        $this->dzqValidate($filter, [
            'sticky' => 'integer|in:0,1',
            'essence' => 'integer|in:0,1',
            'types' => 'array',
            'categoryids' => 'array',
            'sort' => 'integer|in:1,2,3',
            'attention' => 'integer|in:0,1',
        ]);
        $stick = 0;
        $essence = null;
        $types = [];
        $categoryids = [];
        $sort = 1;
        $attention = 0;
        isset($filter['sticky']) && $stick = $filter['sticky'];
        isset($filter['essence']) && $essence = $filter['essence'];
        isset($filter['types']) && $types = $filter['types'];
        isset($filter['categoryids']) && $categoryids = $filter['categoryids'];
        isset($filter['sort']) && $sort = $filter['sort'];
        isset($filter['attention']) && $attention = $filter['attention'];

        $categoryids = Category::instance()->getValidCategoryIds($this->user, $categoryids);
        if (!$categoryids) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '类别参数错误或没有该类别浏览权限');
        }
        //评论排序
        $threads = Thread::query()
            ->whereNull('threads.deleted_at')
            ->where('is_sticky', $stick)
            ->where('is_draft', Thread::IS_NOT_DRAFT);
        !is_null($essence) && $threads = $threads->where('is_essence', $essence);
        if ($sort == Thread::SORT_BY_THREAD) {//按照发帖时间排序
            $threads->orderByDesc('threads.created_at');
        } else if ($sort == Thread::SORT_BY_POST) {//按照评论时间排序
            //添加评论字段posted_at
            $threads->orderByDesc('threads.posted_at');
            //region 临时方法
//            $posts = Post::query()
//                ->selectRaw('max(posts.id) as postId')
//                ->leftJoin('threads', 'posts.thread_id', '=', 'threads.id')
//                ->whereNull('threads.deleted_at')
//                ->where('is_sticky', $stick)
//                ->where('is_essence', $essence)
//                ->where('threads.is_approved', Thread::APPROVED)
//                ->where('posts.is_approved', Post::APPROVED_YES)
//                ->groupBy('posts.thread_id')
//                ->orderByRaw('postId desc');
//
//            $posts = $this->pagination($currentPage, $perPage, $posts);
//            $pageData = $posts['pageData'];
//            $postIds = array_column($pageData, 'postId');
//            $threads = Thread::query()
//                ->selectRaw('threads.*')
//                ->leftJoin('posts', 'posts.thread_id', '=', 'threads.id')
//                ->whereIn('posts.id', $postIds)
//                ->orderByDesc('posts.id');
            //endregion
        }
        //关注
        if ($attention == 1 && !empty($this->user)) {
            $threads->leftJoin('user_follow', 'user_follow.to_user_id', '=', 'threads.user_id')
                ->where('user_follow.from_user_id', $this->user->id);
        }
        !empty($categoryids) && $threads->whereIn('category_id', $categoryids);
        !empty($types) && $threads->whereIn('type', $types);
        $threads = $this->pagination($currentPage, $perPage, $threads);
        return $threads;
    }

    private function getCache($cache, $key)
    {
        $data = $cache->get(CacheKey::LIST_V2_THREADS);
        if ($data) {
            $data = unserialize($data);
            if (isset($data[$key])) {
                $this->outPut(0, '', $data[$key]);
            }
        }
    }

    private function putCache($cache, $key, $threads)
    {
        $data = $cache->get(CacheKey::LIST_V2_THREADS);
        if ($data) {
            $data = unserialize($data);
        } else {
            $data = [];
        }
        $data[$key] = $threads;
        $cache->put(CacheKey::LIST_V2_THREADS, serialize($data), 30 * 60);
    }
}