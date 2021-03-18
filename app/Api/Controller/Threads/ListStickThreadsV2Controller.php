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

use App\Common\ResponseCode;
use App\Models\Post;
use App\Models\Thread;
use Discuz\Base\DzqController;

class ListStickThreadsV2Controller extends DzqController
{

    public function main()
    {
        $categoryId = $this->inPut('categoryId');
        $threads = Thread::query()->select(['id','category_id', 'title']);
        if (!empty($categoryId)) {
            if (!is_array($categoryId)) {
                $categoryId = [$categoryId];
            }
            $threads = $threads->whereIn('category_id', $categoryId);
        }
        $threads = $threads
            ->where('is_sticky', 1)
            ->whereNull('deleted_at')
            ->get();
        $threadIds = $threads->pluck('id')->toArray();
        $posts = Post::query()
            ->whereIn('thread_id', $threadIds)
            ->whereNull('deleted_at')
            ->where('is_first', Post::FIRST_YES)
            ->get()->pluck(null, 'thread_id');
        $data = [];
        foreach ($threads as $thread) {
            $title = $thread['title'];
            $id = $thread['id'];
            if (empty($title)) {
                if (isset($posts[$id])) {
                    $title = $posts[$id]['summary_text'];
                }
            }
            $data [] = [
                'pid' => $thread['id'],
                'categoryId'=>$thread['category_id'],
                'title' => $title
            ];
        }
        $this->outPut(ResponseCode::SUCCESS, '', $data);
    }
}
