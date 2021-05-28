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

namespace App\Api\Controller\AdminV3;

use App\Common\ResponseCode;
use App\Models\ThreadTag;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Models\Thread;
use App\Models\StopWord;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Illuminate\Support\Str;

class CheckThemeList extends DzqController
{

    private $sortFields = [
        'id',
        'is_sticky',
        'post_count',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if (!$this->user->isAdmin()) {
            throw new PermissionDeniedException('没有权限');
        }
        return true;
    }

    public function main()
    {
        $threadType = $this->inPut('threadType'); //置顶加精类型
        $viewCountGt = $this->inPut('viewCountGt'); //浏览次数始
        $viewCountLt = $this->inPut('viewCountLt'); //浏览次数结
        $postCountGt = $this->inPut('postCountGt'); //回复次数始
        $postCountLt = $this->inPut('postCountLt'); //回复次数结
        $highlight = $this->inPut('highlight');  //是否显示敏感词
        $isApproved = $this->inPut('isApproved') ? intval($this->inPut('isApproved')) : 0; //0未审核 1已忽略
        $threadId = intval($this->inPut('threadId')); // 帖子id
        $q = $this->inPut('q'); //内容
        $isDeleted = $this->inPut('isDeleted'); //帖子是否删除
        $nickname = $this->inPut('nickname'); //用户名
        $page = intval($this->inPut('page')); //分页
        $perPage = intval($this->inPut('perPage')); //分页
        $createdAtBegin = $this->inPut('createdAtBegin'); //开始时间
        $createdAtEnd = $this->inPut('createdAtEnd'); //结束时间
        $deletedAtBegin = $this->inPut('deletedAtBegin'); //删除开始时间
        $deletedAtEnd = $this->inPut('deletedAtEnd'); //删除结束时间
        $deletedNickname = $this->inPut('deletedNickname'); //删除帖子用户
        $categoryId = intval($this->inPut('categoryId')); //分类id
        $sort = $this->inPut('sort') ? $this->inPut('sort') : '-updated_at';     //排序

        $query = Thread::query()
            ->select(
                'threads.id as thread_id', 'threads.user_id', 'threads.title', 'threads.post_count', 'threads.view_count',
                'threads.is_approved', 'threads.updated_at' ,'threads.deleted_user_id' ,'threads.deleted_at', 'threads.price',
                'posts.content',
                'users.nickname',
                'categories.name'
            )
            ->leftJoin('posts','threads.id','posts.thread_id')
            ->where('posts.is_first',true);

        //是否审核
        $query->where('threads.is_approved', $isApproved);

        //浏览次数
        if ($viewCountGt !== '') {
            $query->where('threads.view_count', '>=', intval($viewCountGt));
        }

        //浏览次数
        if ($viewCountLt !== '') {
            $query->where('threads.view_count', '<=', intval($viewCountLt));
        }

        //回复次数
        if ($postCountGt !== '') {
            $query->where('threads.post_count', '>=', intval($postCountGt));
        }

        //回复次数
        if ($postCountLt !== '') {
            $query->where('threads.post_count', '<=', intval($postCountLt));
        }

        /*
         * 置顶 1
         * 加精 2
         * 置顶并精华主题 3
         * 付费首页主题 4
         */
        if ($threadType == 1) {
            $query->where('threads.is_sticky', 1);
        } else if ($threadType == 2) {
            $query->where('threads.is_essence', 1);
        } else if ($threadType == 3) {
            $query->where('threads.is_sticky', 1)
                ->where('threads.is_essence', 1);
        } else if ($threadType == 4){
            $query->where('threads.is_site', 1)
                ->where(function ($query) {
                     $query->orWhere('threads.price', '>', 0)
                     ->orWhere('threads.attachment_price', '>' ,0);
                 });
        }

        //帖子id筛选
        if (!empty($threadId)) {
            $query->where('threads.id', $threadId);
        }

        //内容筛选
        if (!empty($q)) {
            $query->where('threads.title','like','%'.$q.'%');
        }

        // 回收站
        if ($isDeleted == 'yes') {
            // 只看回收站帖子
            $query->whereNotNull('threads.deleted_at');
        } elseif ($isDeleted == 'no') {
            // 不看回收站帖子
            $query->whereNull('threads.deleted_at');
        }

        //类型筛选
        $query->leftJoin('categories', 'categories.id', '=', 'threads.category_id');
        if (!empty($categoryId)) {
            $query->where('threads.category_id', $categoryId);
        }

        //发帖时间筛选
        if (!empty($createdAtBegin) && !empty($createdAtEnd)) {
            $query->whereBetween('threads.updated_at', [$createdAtBegin, $createdAtEnd]);
        }

        //发帖删除时间筛选
        if (!empty($deletedAtBegin) && !empty($deletedAtEnd)) {
            $query->whereBetween('threads.deleted_at', [$deletedAtBegin, $deletedAtEnd]);
        }

        //用户删除用户昵称
        if (!empty($deletedNickname)) {
            $query->addSelect('users1.nickname as deleted_user')
                ->leftJoin('users as users1', 'users1.id','=','threads.deleted_user_id')
                ->where('users1.nickname','like','%'.$deletedNickname.'%');
        }

        //用户昵称筛选
        $query->leftJoin('users', 'users.id', '=', 'threads.user_id');
        if (!empty($nickname)) {
            $query->where('users.nickname', 'like','%'.$nickname.'%');
        }

        //排序
        $sort = ltrim(Str::snake($sort), '-');
        if (!in_array($sort, $this->sortFields)) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '不合法的排序字段:'.$sort);
        }

        //排序
        $query = $query->orderBy('threads.'.$sort,
        Str::startsWith($sort, '-') ? 'desc' : 'asc');

        //分页
        $pagination = $this->pagination($page, $perPage, $query);

        // 高亮敏感词
        if ($highlight == 'yes') {
            $stopWord = StopWord::query()->where('ugc',StopWord::MOD)->get(['find'])->toArray();
            $replace = array_column($stopWord, 'find');

            foreach ($pagination['pageData'] as $key=>$val){
                foreach ($replace as $v){
                    $val['title']  = str_replace($v,'<span class="highlight">' . $v . '</span>',$val['title']);
                }
                $pagination['pageData'][$key]['title'] = $val['title'];
            }
        }

        $this->outPut(ResponseCode::SUCCESS,'', $this->camelData($pagination));
    }

}
