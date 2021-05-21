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
use App\Models\Group;
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadTag;
use App\Models\ThreadTom;
use App\Modules\ThreadTom\TomConfig;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;

class UpdateThreadController extends DzqController
{
    use ThreadTrait;

    private $thread;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $this->thread = $this->thread = Thread::query()
            ->where(['id' => $this->inPut('threadId')])
            ->whereNull('deleted_at')
            ->first();
        if (!$this->thread) {
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
        }

        return $userRepo->canEditThread($this->user, $this->thread);
    }

    public function main()
    {
        $threadId = $this->inPut('threadId');
        $thread = $this->thread;
        $post = Post::getOneActivePost($threadId);
        if (empty($post)) {
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
        }
        $result = $this->updateThread($thread, $post);
        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }

    private function updateThread($thread, $post)
    {
        $db = $this->getDB();
        $db->beginTransaction();
        try {
            $result = $this->executeEloquent($thread, $post);
            $db->commit();
            return $result;
        } catch (\Exception $e) {
            $db->rollBack();
            $this->info('updateThread_error_' . $this->user->id, $e->getMessage());
            $this->outPut(ResponseCode::DB_ERROR, $e->getMessage());
        }
    }

    private function executeEloquent($thread, $post)
    {
        $content = $this->inPut('content');//非必填项

        if (!empty($content['text'])) {
            $content['text'] = $this->optimizeEmoji($content['text']);
        }

        //更新thread数据
        $this->saveThread($thread, $content);
        //更新post数据
        $this->savePost($post, $content);
        //插入话题
        $this->saveTopic($thread, $content);
        //更新tom数据
        $tomJsons = $this->saveThreadTom($thread, $content, $post);
        return $this->getResult($thread, $post, $tomJsons);
    }


    private function saveThread($thread, &$content)
    {
        $title = $this->inPut('title');//非必填项
        $categoryId = $this->inPut('categoryId');
        $price = $this->inPut('price');
        $attachmentPrice = $this->inPut('attachmentPrice');
        $freeWords = $this->inPut('freeWords');
        $position = $this->inPut('position');
        $isAnonymous = $this->inPut('anonymous');
        $isDraft = $this->inPut('draft');

        !empty($position) && $this->dzqValidate($position, [
            'longitude' => 'required',
            'latitude' => 'required',
            'address' => 'required',
            'location' => 'required'
        ]);
        !empty($title) && $thread->title = $title;
        !empty($categoryId) && $thread->category_id = $categoryId;
        if (!empty($position)) {
            $thread->longitude = $position['longitude'];
            $thread->latitude = $position['latitude'];
            $thread->address = $position['address'];
            $thread->location = $position['location'];
        }
        floatval($price) > 0 && $thread->price = floatval($price);
        floatval($attachmentPrice) > 0 && $thread->attachment_price = floatval($attachmentPrice);
        floatval($freeWords) > 0 && $thread->free_words = floatval($freeWords);
        [$newTitle, $newContent] = $this->boolApproved($title, $content['text'], $isApproved);
        $content['text'] = $newContent;
        !empty($title) && $thread->title = $newTitle;
        if ($isApproved) {
            $thread->is_approved = Thread::BOOL_YES;
        } else {
            $thread->is_approved = Thread::BOOL_NO;
        }
        $isDraft && $thread->is_draft = Thread::BOOL_YES;
        !empty($isAnonymous) && $thread->is_anonymous = Thread::BOOL_YES;
        $thread->save();
        if ($isApproved && !$isDraft) {
            $this->user->refreshThreadCount();
            $this->user->save();
            Category::refreshThreadCountV3($categoryId);
        }
    }

    private function savePost($post, $content)
    {
        [$ip, $port] = $this->getIpPort();
        $post->content = $content['text'];;
        $post->ip = $ip;
        $post->port = $port;
        $post->is_first = Post::FIRST_YES;
        $post->is_approved = Post::APPROVED;
        $post->save();
    }

    private function saveThreadTom($thread, $content, $post)
    {
        $threadId = $thread->id;
        $tags = [];
        $tomJsons = $this->tomDispatcher($content, null, $thread->id, $post->id);
        if (!empty($content['text'])) {
            $tags[] = [
                'thread_id' => $thread['id'],
                'tag' => TomConfig::TOM_TEXT,
            ];
        }
        foreach ($tomJsons as $key => $value) {
            $tomId = $value['tomId'];
            $operation = $value['operation'];
            $body = $value['body'];
            $tags[] = [
                'thread_id' => $threadId,
                'tag' => $value['tomId']
            ];
            switch ($operation) {
                case $this->CREATE_FUNC:
                    ThreadTom::query()->insert([
                        'thread_id' => $threadId,
                        'tom_type' => $tomId,
                        'key' => $key,
                        'value' => json_encode($body, 256),
                        'status' => ThreadTom::STATUS_ACTIVE
                    ]);
                    break;
                case $this->DELETE_FUNC:
                    ThreadTom::query()
                        ->where(['thread_id' => $threadId, 'tom_type' => $tomId, 'status' => ThreadTom::STATUS_ACTIVE])
                        ->update(['status' => ThreadTom::STATUS_DELETE]);
                    break;
                case $this->UPDATE_FUNC:
                    ThreadTom::query()
                        ->where(['thread_id' => $threadId, 'tom_type' => $tomId, 'key' => $key, 'status' => ThreadTom::STATUS_ACTIVE])
                        ->update(['value' => json_encode($body, 256)]);
                    break;
                default:
                    $this->outPut(ResponseCode::UNKNOWN_ERROR, 'operation ' . $operation . ' not exist.');
            }
        }
        $this->saveThreadTag($threadId, $tags);
        return $tomJsons;
    }

    private function saveThreadTag($threadId, $tags)
    {
        ThreadTag::query()->where('thread_id', $threadId)->delete();
        ThreadTag::query()->insert($tags);
    }

    private function getResult($thread, $post, $tomJsons)
    {
        $user = $this->user;
        $group = Group::getGroup($user->id);
        return $this->packThreadDetail($user, $group, $thread, $post, $tomJsons, true);
    }
}
