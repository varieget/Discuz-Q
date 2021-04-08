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
        $thread = ThreadText::query()->where('id', $threadId)->where(['status' => ThreadText::STATUS_ACTIVE])->first();
        if (empty($thread)) {
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
        }
        if (!$this->canEditThread($this->user, $thread->category_id, $thread->user_id)) {
            $this->outPut(ResponseCode::UNAUTHORIZED);
        }
        $this->updateThread($thread);
        $this->outPut(ResponseCode::SUCCESS);
    }

    private function updateThread($thread)
    {
        $content = $this->inPut('content');//非必填项
        $db = $this->getDB();
        $db->beginTransaction();
        try {
            $this->executeEloquent($thread, $content);
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            $this->info('updateThread_error_' . $this->user->id, $e->getMessage());
            $this->outPut(ResponseCode::DB_ERROR, $e->getMessage());
        }
    }

    private function executeEloquent(ThreadText $thread, $content)
    {
        list($text, $tomJson) = $this->tomDispatcher($content);
        //更新thread_text
        $title = $this->inPut('title');//非必填项
        $categoryId = $this->inPut('categoryId');
        $position = $this->inPut('position');
        $isAnonymous = $this->inPut('anonymous');//非必须
        $summary = $this->inPut('summary');//非必须
        !empty($position) && $this->dzqValidate($position, [
            'longitude' => 'required',
            'latitude' => 'required',
            'address' => 'required',
            'location' => 'required'
        ]);
        list($ip, $port) = $this->getIpPort();
        !empty($title) && $thread->title = $title;
        !empty($categoryId) && $thread->category_id = $categoryId;
        !empty($summary) && $thread->summary = $summary;
        !empty($text) && $thread->text = $text;
        !empty($isAnonymous) && $thread->is_anonymous = $isAnonymous;
        $thread->ip = $ip;
        $thread->port = $port;
        if (!empty($position)) {
            $thread->longitude = $position['longitude'];
            $thread->latitude = $position['latitude'];
            $thread->address = $position['address'];
            $thread->location = $position['location'];
        }
        $thread->save();
        $threadId = $thread->id;
        //更新thread_tom
        foreach ($tomJson as $key => $value) {
            $tomId = $value['tomId'];
            $operation = $value['operation'];
            $body = $value['body'];
            switch ($operation) {
                case $this->CREATE_FUNC:
                    ThreadTom::query()->insert([
                        'thread_id' => $threadId,
                        'tom_type' => $tomId,
                        'key' => $key,
                        'value' => $body,
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
                        ->where(['thread_id' => $threadId, 'tom_type' => $tomId, 'status' => ThreadTom::STATUS_ACTIVE])
                        ->update(['value' => json_encode($body, 256)]);
                    break;
                case $this->SELECT_FUNC:
                    break;
                default:
                    $this->outPut(ResponseCode::UNKNOWN_ERROR, 'func not exist.');
            }
        }
    }
}
