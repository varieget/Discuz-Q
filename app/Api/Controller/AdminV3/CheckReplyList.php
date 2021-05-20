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
use App\Repositories\UserRepository;
use App\Models\Post;
use App\Models\StopWord;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Illuminate\Support\Str;

class CheckReplyList extends DzqController
{

    private $sortFields = [
        'id',
        'reply_count',
        'like_count',
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
        $isDeleted = $this->inPut('isDeleted'); //是否删除
        $nickname = $this->inPut('nickname'); //用户名
        $page = intval($this->inPut('page')); //分页
        $perPage = intval($this->inPut('perPage')); //分页
        $q = $this->inPut('q'); //内容
        $isApproved = intval($this->inPut('isApproved')); //0未审核 1已忽略
        $createdAtBegin = $this->inPut('createdAtBegin'); //开始时间
        $createdAtEnd = $this->inPut('createdAtEnd'); //结束时间
        $categoryId = intval($this->inPut('categoryId')); //分类id
        $highlight = $this->inPut('highlight');  //是否显示敏感词
        $sort = $this->inPut('sort') ? $this->inPut('sort') : '-updated_at';//排序

        $query = Post::query()
            ->select('posts.id', 'posts.thread_id',
                'posts.content', 'posts.ip', 'posts.updated_at','users.nickname','threads.title');

        $query->where('posts.is_approved', $isApproved);

        // 回收站
        if ($isDeleted == 'yes' && $this->user->can('viewTrashed')) {
            // 只看回收站帖子
            $query->whereNotNull('posts.deleted_at');
        } elseif ($isDeleted == 'no') {
            // 不看回收站帖子
            $query->whereNull('posts.deleted_at');
        }

        //用户昵称筛选
        $query->leftJoin('users', 'users.id', '=', 'posts.user_id');
        if (!empty($nickname)) {
            $query->where('users.nickname', $nickname);
        }

        //内容筛选
        if (!empty($q)) {
            $query->where('posts.content','like','%'.$q.'%');
        }

        //时间筛选
        if (!empty($createdAtBegin) && !empty($createdAtEnd)) {
            $query->whereBetween('posts.updated_at', [$createdAtBegin, $createdAtEnd]);
        }

        //分类筛选
        $query->leftJoin('threads', 'posts.thread_id', '=', 'threads.id');
        if (!empty($categoryId)) {
            $query->where('threads.category_id', $categoryId);
        }

        //排序
        $sort = ltrim(Str::snake($sort), '-');
        if (!in_array($sort, $this->sortFields)) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '不合法的排序字段:'.$sort);
        }
        $query = $query->orderBy('posts.'.$sort,
        Str::startsWith($sort, '-') ? 'desc' : 'asc');

        $pagination = $this->pagination($page, $perPage, $query);

        // 高亮敏感词
        if ($highlight == 'yes') {
            $stopWord = StopWord::query()->where('ugc',StopWord::MOD)->get(['find'])->toArray();
            $replace = array_column($stopWord, 'find');

            foreach ($pagination['pageData'] as $key=>$val){
                foreach ($replace as $v){
                    $val['content']  = str_replace($v,'<span class="highlight">' . $v . '</span>',$val['content']);
                }
                $pagination['pageData'][$key]['content'] = $val['content'];
            }
        }

        $this->outPut(ResponseCode::SUCCESS,'', $this->camelData($pagination));
    }

}
