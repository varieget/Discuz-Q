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
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadTag;
use App\Models\ThreadText;
use App\Models\ThreadTom;
use App\Modules\ThreadTom\TomTrait;
use Discuz\Base\DzqController;

class UpdateThreadController extends DzqController
{
    use TomTrait;

    public function main()
    {
        $threadId = $this->inPut('threadId');
        $thread = Thread::getOneActiveThread($threadId);
        $post = Post::getOneActivePost($threadId);
        if (empty($thread) || empty($post)) {
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
        }
        if (!$this->canEditThread($this->user, $thread->category_id, $thread->user_id)) {
            $this->outPut(ResponseCode::UNAUTHORIZED);
        }
        $result = $this->updateThread($thread, $post);
        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }

    private function updateThread($thread, $post)
    {
        $content = $this->inPut('content');//非必填项
        $db = $this->getDB();
        $db->beginTransaction();
        try {
            $result = $this->executeEloquent($thread, $post, $content);
            $db->commit();
            return $result;
        } catch (\Exception $e) {
            $db->rollBack();
            $this->info('updateThread_error_' . $this->user->id, $e->getMessage());
            $this->outPut(ResponseCode::DB_ERROR, $e->getMessage());
        }
    }

    private function executeEloquent($thread, $post, $content)
    {
        $text = $content['text'];
        $tomJsons = $this->tomDispatcher($content, null, $thread->id);
        //更新thread_text
        $this->saveThread($thread);
        $this->savePost($post, $text);
        //更新thread_tom
        $this->saveThreadTom($thread, $tomJsons);
        return $this->getResult($thread, $tomJsons);
    }


    private function saveThread($thread)
    {
        $title = $this->inPut('title');//非必填项
        $categoryId = $this->inPut('categoryId');
        $position = $this->inPut('position');
        $isAnonymous = $this->inPut('anonymous');//非必须
        !empty($position) && $this->dzqValidate($position, [
            'longitude' => 'required',
            'latitude' => 'required',
            'address' => 'required',
            'location' => 'required'
        ]);
        !empty($title) && $thread->title = $title;
        !empty($categoryId) && $thread->category_id = $categoryId;
        !empty($isAnonymous) && $thread->is_anonymous = $isAnonymous;
        if (!empty($position)) {
            $thread->longitude = $position['longitude'];
            $thread->latitude = $position['latitude'];
            $thread->address = $position['address'];
            $thread->location = $position['location'];
        }
        //todo 判断是否需要审核
        $dataThread['is_approved'] = Thread::BOOL_YES;
        $thread->save();
    }

    private function savePost($post, $text)
    {
        list($ip, $port) = $this->getIpPort();
        $post->content = $text;
        $post->ip = $ip;
        $post->port = $port;
        $post->is_first = Post::FIRST_YES;
        $post->is_approved = Post::APPROVED;
        $post->save();
    }

    private function saveThreadTom($thread, $tomJson)
    {
        $threadId = $thread->id;
        $tags = [];
        foreach ($tomJson as $key => $value) {
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
    }

    private function saveThreadTag($threadId, $tags)
    {
        ThreadTag::query()->where('thread_id', $threadId)->delete();
        ThreadTag::query()->insert($tags);
    }

    private function getResult($thread, $tomJsons)
    {
        return [
            'threadId' => $thread['id'],
            'userId' => $thread['user_id'],
            'categoryId' => $thread['category_id'],
            'title' => $thread['title'],
            'price' => $thread['price'],
            'attachmentPrice' => $thread['attachmentPrice'],
            'position' => [
                'longitude' => $thread['longitude'],
                'latitude' => $thread['latitude'],
                'address' => $thread['address'],
                'location' => $thread['location']
            ],
            'isAnonymous' => $thread['is_anonymous'],
            'content' => $this->tomDispatcher($tomJsons, $this->SELECT_FUNC)
        ];
    }
}
