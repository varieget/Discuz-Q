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
use App\Models\Permission;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Thread;
use App\Models\ThreadStickSort;

trait ThreadStickTrait
{
    public function getData($isAdmin = false)
    {
        $categoryIds = $this->inPut('categoryIds');
        $threads = Thread::query()->select(['id', 'category_id', 'title', 'updated_at','user_id'])->orderByDesc('updated_at');
        if (!empty($categoryIds)) {
            if (!is_array($categoryIds)) {
                $categoryIds = [$categoryIds];
            }
        }
        if(!$isAdmin){
            $isMiniProgramVideoOn = Setting::isMiniProgramVideoOn();
            if (!$isMiniProgramVideoOn) {
                $threads = $threads->where('type', '<>', Thread::TYPE_OF_VIDEO);
            }
        }

        $permissions = Permission::getUserPermissions($this->user);
        $categoryIds = Category::instance()->getValidCategoryIds($this->user, $categoryIds);
        if (!$categoryIds) {
            $this->outPut(ResponseCode::SUCCESS, '', []);
        } else {
            $threads = $threads->whereIn('category_id', $categoryIds);
        }

        $threads = $threads
            ->where('is_sticky', Thread::BOOL_YES)
            ->whereNull('deleted_at')
            ->whereNotNull("user_id")
            ->where('is_draft', Thread::BOOL_NO)
            ->where('is_display', Thread::BOOL_YES)
            ->where('is_approved', Thread::BOOL_YES)
            ->get();
        $threadIds = $threads->pluck('id')->toArray();

        $posts = Post::query()
            ->whereIn('thread_id', $threadIds)
            ->whereNull('deleted_at')
            ->where('is_first', Post::FIRST_YES)
            ->get()->pluck(null, 'thread_id');
        $data = [];
        $linkString = '';

        $threadStickSort = ThreadStickSort::query()->select("thread_id","sort")->get()->toArray();
        $sort = array_column($threadStickSort,'sort','thread_id');

        foreach ($threads as $thread) {
            $title = $thread['title'];
            $id = $thread['id'];
            if (empty($title)) {
                if (isset($posts[$id])) {
                    $title = Post::instance()->getContentSummary($posts[$id]);
                }
            }
            $linkString .= $title;

            $resultData = [
                'threadId' => $thread['id'],
                'categoryId' => $thread['category_id'],
                'title' => $title,
                'updatedAt' => date('Y-m-d H:i:s', strtotime($thread['updated_at'])),
                'canViewPosts' => $this->canViewPosts($thread, $permissions),
                'sort' => !empty($sort[$id]) ? $sort[$id] : 0
            ];

            $data [] = $resultData;
        }
        $data = collect($data)->sortBy('sort')->values()->toArray();
        return $data;
    }


    private function canViewPosts($thread, $permissions)
    {
        if ($this->user->isAdmin() || $this->user->id == $thread['user_id']) {
            return true;
        }
        $viewPostStr = 'category' . $thread['category_id'] . '.thread.viewPosts';
        if (in_array('thread.viewPosts', $permissions) || in_array($viewPostStr, $permissions)) {
            return true;
        }
        return false;
    }
}
