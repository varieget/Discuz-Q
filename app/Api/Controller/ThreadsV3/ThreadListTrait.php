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
use App\Models\Attachment;
use App\Models\Category;
use App\Models\GroupUser;
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadUser;
use App\Models\ThreadTag;
use App\Models\ThreadTom;
use App\Models\ThreadVideo;
use App\Models\User;
use App\Modules\ThreadTom\PreQuery;
use App\Modules\ThreadTom\TomConfig;
use Illuminate\Support\Str;

trait ThreadListTrait
{
    private function getFullThreadData($threadCollection)
    {
        $threadList = $threadCollection->toArray();
        $userIds = array_unique(array_column($threadList, 'user_id'));
        $groups = GroupUser::instance()->getGroupInfo($userIds);
        $groups = array_column($groups, null, 'user_id');
        $users = User::instance()->getUsers($userIds);
        $users = array_column($users, null, 'id');
        $threadIds = array_column($threadList, 'id');
        $posts = Post::instance()->getPosts($threadIds);
        $postsByThreadId = array_column($posts, null, 'thread_id');
        $toms = ThreadTom::query()->whereIn('thread_id', $threadIds)->where('status', ThreadTom::STATUS_ACTIVE)->get();
        $tags = [];
        ThreadTag::query()->whereIn('thread_id', $threadIds)->get()->each(function ($item) use (&$tags) {
            $tags[$item['thread_id']][] = $item->toArray();
        });
        $inPutToms = $this->preQuery($toms, $threadCollection, $threadIds);
        $result = [];
        $linkString = '';
        foreach ($threadList as $thread) {
            $threadId = $thread['id'];
            $userId = $thread['user_id'];
            $user = empty($users[$userId]) ? false : $users[$userId];
            $group = empty($groups[$userId]) ? false : $groups[$userId];
            $post = empty($postsByThreadId[$threadId]) ? false : $postsByThreadId[$threadId];
            $tomInput = empty($inPutToms[$threadId]) ? false : $inPutToms[$threadId];
            $threadTags = [];
            isset($tags[$threadId]) && $threadTags = $tags[$threadId];
            $result[] = $this->packThreadDetail($user, $group, $thread, $post, $tomInput, false, $threadTags);
            $linkString .= ($thread['title'] . $post['content']);
        }
        list($search, $replace) = Thread::instance()->getReplaceString($linkString);
        foreach ($result as &$item) {
            $item['title'] = str_replace($search, $replace, $item['title']);
            $item['content']['text'] = str_replace($search, $replace, $item['content']['text']);
        }
        return $result;
    }

    /**
     * @desc 预加载列表页数据
     * @param $toms
     * @param $threadCollection
     * @param $threadIds
     * @return array
     */
    private function preQuery($toms, $threadCollection, $threadIds)
    {
        $inPutToms = [];
        $attachmentIds = [];
        $threadVideoIds = [];
        foreach ($toms as $tom) {
            $value = json_decode($tom['value'], true);
            switch ($tom['tom_type']) {
                case TomConfig::TOM_IMAGE:
                    isset($value['imageIds']) && $attachmentIds = array_merge($attachmentIds, $value['imageIds']);
                    break;
                case TomConfig::TOM_DOC:
                    isset($value['docIds']) && $attachmentIds = array_merge($attachmentIds, $value['docIds']);
                    break;
                case TomConfig::TOM_VIDEO:
                    isset($value['videoId']) && $threadVideoIds[] = $value['videoId'];
                    break;
                case TomConfig::TOM_AUDIO:
                    isset($value['audioId']) && $threadVideoIds[] = $value['audioId'];
                    break;
            }
            $inPutToms[$tom['thread_id']][$tom['key']] = $this->buildTomJson($tom['thread_id'], $tom['tom_type'], $this->SELECT_FUNC, $value);
        }
        $attachmentIds = array_unique($attachmentIds);
        $threadVideoIds = array_unique($threadVideoIds);
        $attachments = Attachment::query()->whereIn('id', $attachmentIds)->get()->pluck(null, 'id');
        $threadVideos = ThreadVideo::query()->whereIn('id', $threadVideoIds)->where('status', ThreadVideo::VIDEO_STATUS_SUCCESS)->get()->pluck(null, 'id');
        $threadList = $threadCollection->pluck(null, 'id');
        $favorite = ThreadUser::query()->whereIn('thread_id', $threadIds)->where('user_id', $this->user->id)->get()->pluck(null, 'thread_id');
        $categories = Category::getCategories();
        app()->instance(PreQuery::THREAD_LIST_ATTACHMENTS, $attachments);
        app()->instance(PreQuery::THREAD_LIST_VIDEO, $threadVideos);
        app()->instance(PreQuery::THREAD_LIST, $threadList);
        app()->instance(PreQuery::THREAD_LIST_CATEGORIES, $categories);
        app()->instance(PreQuery::THREAD_LIST_FAVORITE, $favorite);
        return $inPutToms;
    }
}
