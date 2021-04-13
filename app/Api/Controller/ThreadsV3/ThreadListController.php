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
use App\Models\Category;
use App\Models\Thread;
use App\Models\ThreadHot;
use App\Models\ThreadText;
use App\Models\ThreadTom;
use App\Modules\ThreadTom\TomTrait;
use Discuz\Base\DzqController;

class ThreadListController extends DzqController
{

    use TomTrait;

    public function main()
    {
        $filter = $this->inPut('filter');
        $currentPage = $this->inPut('page');
        $perPage = $this->inPut('perPage');
        $sequence = $this->inPut('sequence');//默认首页
        $categoryId = $this->inPut('categoryId');
        if (!$this->canViewThread($this->user, $categoryId)) {
            $this->outPut(ResponseCode::UNAUTHORIZED);
        }
        if ($sequence) {
            $threads = $this->getDefaultHomeThreads($filter, $currentPage, $perPage);
        } else {
            $threads = $this->getFilterThreads($filter, $currentPage, $perPage);
        }
        $threadList = $threads['pageData'];
        $threadIds = array_column($threadList, 'id');
        $toms = ThreadTom::query()->whereIn('thread_id', $threadIds)->where('status', ThreadTom::STATUS_ACTIVE)->get();
        $threadHot = ThreadHot::query()
            ->whereIn('thread_id', $threadIds)
            ->get()->pluck(null, 'thread_id');
        $inPutToms = [];
        foreach ($toms as $tom) {
            $inPutToms[$tom['thread_id']][$tom['key']] = [
                'threadId' => $tom['thread_id'],
                'tomId' => $tom['tom_type'],
                'operation' => $this->SELECT_FUNC,
                'body' => $tom['value']
            ];
        }

        $pageData = [];
        foreach ($threadList as $item) {
            $threadId = $item['id'];
            $content = [
                'text' => $item['text'],
                'indexes' => null
            ];
            if (isset($inPutToms[$threadId])) {
                $content['indexes'] = $this->tomDispatcher($inPutToms[$threadId]);
            }
            $position = [
                'longitude' => $item['longitude'],
                'latitude' => $item['latitude'],
                'address' => $item['address'],
                'location' => $item['location']
            ];
            $pageData[] = [
                'threadId' => $threadId,
                'userId' => $item['user_id'],
                'categoryId' => $item['category_id'],
                'title' => $item['title'],
                'summary' => $item['summary'],
                'position' => $position,
                'isSticky' => $item['is_sticky'],
                'isEssence' => $item['is_essence'],
                'isAnonymous' => $item['is_anonymous'],//匿名贴不传userid
                'isSite' => $item['is_site'],
                'hotData' => ThreadHot::instance()->getHotData($threadHot),
                'content' => $content
            ];
        }
        $threads['pageData'] = $pageData;
        $this->outPut(0, '', $threads);
    }

    function getFilterThreads($filter, $currentPage, $perPage)
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
        $essence = null;
        $types = [];
        $categoryids = [];
        $sort = ThreadText::SORT_BY_CREATE_TIME;
        $attention = 0;
        isset($filter['sticky']) && $stick = $filter['sticky'];
        isset($filter['essence']) && $essence = $filter['essence'];
        isset($filter['types']) && $types = $filter['types'];
        isset($filter['categoryids']) && $categoryids = $filter['categoryids'];
        isset($filter['sort']) && $sort = $filter['sort'];
        isset($filter['attention']) && $attention = $filter['attention'];
        $categoryids = Category::instance()->getValidCategoryIds($this->user, $categoryids);
        if (!$categoryids) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '没有浏览权限');
        }
        $threads = ThreadText::query()
            ->from('thread_text as text')
            ->where(['is_sticky' => ThreadText::FIELD_NO, 'status' => ThreadText::STATUS_ACTIVE]);
        !empty($essence) && $threads = $threads->where('is_essence', $essence);

        if (!empty($types)) {
            $threads = $threads->leftJoin('thread_tag as tag', 'tag.thread_id', '=', 'text.user_id')
                ->whereIn('tag', $types);
        }

        if (!empty($sort)) {
            if ($sort == ThreadText::SORT_BY_CREATE_TIME) {//按照发帖时间排序
                $threads->orderByDesc('text.created_at');
            } else if ($sort == ThreadText::SORT_BY_LAST_POST_TIME) {//按照评论时间排序
                $threads->leftJoin('thread_hot as hot', 'text.id', '=', 'hot.thread_id');
                $threads->orderByDesc('hot.last_post_time');
            }
        }
        //关注
        if ($attention == 1 && !empty($this->user)) {
            $threads->leftJoin('user_follow as follow', 'follow.to_user_id', '=', 'text.user_id')
                ->where('follow.from_user_id', $this->user->id);
        }
        !empty($categoryids) && $threads->whereIn('category_id', $categoryids);
        $threads = $this->pagination($currentPage, $perPage, $threads);
        return $threads;
    }

    function getDefaultHomeThreads($filter, $currentPage, $perPage)
    {
        return $this->pagination($currentPage, $perPage, null);

    }
}
