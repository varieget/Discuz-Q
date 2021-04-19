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
use App\Models\Group;
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadTag;
use App\Models\ThreadTom;
use Discuz\Base\DzqController;

class CreateThreadController extends DzqController
{
    use ThreadTrait;

    public function main()
    {
        $this->limitCreateThread();
        //发帖权限
        $categoryId = $this->inPut('categoryId');
        if (!$this->canCreateThread($this->user, $categoryId)) {
            $this->outPut(ResponseCode::UNAUTHORIZED);
        }
        !empty($position) && $this->dzqValidate($position, [
            'longitude' => 'required',
            'latitude' => 'required',
            'address' => 'required',
            'location' => 'required'
        ]);
        $result = $this->createThread();
        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }


    /**
     * @desc 发布一个新帖子
     */
    private function createThread()
    {
        $db = $this->getDB();
        $db->beginTransaction();
        try {
            $result = $this->executeEloquent();
            //todo 发帖后的消息通知等
            $db->commit();
            return $result;
        } catch (\Exception $e) {
            $db->rollBack();
            $this->info('createThread_error_' . $this->user->id, $e->getMessage());
            $this->outPut(ResponseCode::DB_ERROR, $e->getMessage());
        }
    }

    private function executeEloquent()
    {
        $content = $this->inPut('content');
        //插入thread数据
        $thread = $this->saveThread($content);
        //插入post数据
        $post = $this->savePost($thread, $content);
        //插入tom数据
        $tomJsons = $this->saveTom($thread, $content);
        return $this->getResult($thread, $post, $tomJsons);
    }

    private function saveThread($content)
    {
        $thread = new Thread();
        $userId = $this->user->id;
        $categoryId = $this->inPut('categoryId');
        $title = $this->inPut('title');//title没有则自动生成
        $price = $this->inPut('price');
        $attachmentPrice = $this->inPut('attachmentPrice');
        $freeWords = $this->inPut('freeWords');
        $position = $this->inPut('position');
        $isAnonymous = $this->inPut('anonymous');
        if (empty($content)) $this->outPut(ResponseCode::INVALID_PARAMETER, '缺少 content 参数');
        if (empty($categoryId)) $this->outPut(ResponseCode::INVALID_PARAMETER, '缺少 categoryId 参数');
        empty($title) && $title = Post::autoGenerateTitle($content['text']);
        $dataThread = [
            'user_id' => $userId,
            'category_id' => $categoryId,
            'title' => $title,
            'post_count' => 1
        ];
        !empty($price) && $dataThread['price'] = $price;
        !empty($attachmentPrice) && $dataThread['attachmentPrice'] = $attachmentPrice;
        !empty($freeWords) && $dataThread['free_words'] = $freeWords;
        if (!empty($position)) {
            $dataThread['longitude'] = $position['longitude'];
            $dataThread['latitude'] = $position['latitude'];
            $dataThread['address'] = $position['address'];
            $dataThread['location'] = $position['location'];
        } else {
            $dataThread['address'] = '';
            $dataThread['location'] = '';
        }
        //todo 判断是否需要审核
        $dataThread['is_approved'] = Thread::BOOL_YES;
        !empty($isAnonymous) && $dataThread['is_anonymous'] = Thread::BOOL_YES;
        $thread->setRawAttributes($dataThread);
        $thread->save();
        return $thread;
    }

    private function savePost($thread, $content)
    {
        $text = $content['text'];
        $post = new Post();
        list($ip, $port) = $this->getIpPort();
        $dataPost = [
            'user_id' => $this->user->id,
            'thread_id' => $thread['id'],
            'content' => $text,
            'ip' => $ip,
            'port' => $port,
            'is_first' => Post::FIRST_YES,
            'is_approved' => Post::APPROVED
        ];
        $post->setRawAttributes($dataPost);
        $post->save();
        return $post;
    }

    private function saveTom($thread, $content)
    {
        $indexes = $content['indexes'];
        $attrs = [];
        $tomJsons = $this->tomDispatcher($indexes, null, $thread['id']);
        $tags = [];
        foreach ($tomJsons as $key => $value) {
            $attrs[] = [
                'thread_id' => $thread['id'],
                'tom_type' => $value['tomId'],
                'key' => $key,
                'value' => json_encode($value['body'], 256)
            ];
            $tags[] = [
                'thread_id' => $thread['id'],
                'tag' => $value['tomId']
            ];
        }
        ThreadTom::query()->insert($attrs);
        //添加tag类型
        ThreadTag::query()->insert($tags);
        return $tomJsons;
    }

    private function getResult($thread, $post, $tomJsons)
    {
        $user = $this->user;
        $group = Group::getGroup($user->id);
        return $this->packThreadDetail($user, $group, $thread, $post, $tomJsons, true);
    }

    private function limitCreateThread()
    {
        $threadFirst = Thread::query()
            ->select(['id', 'user_id', 'category_id', 'created_at'])
            ->where('user_id', $this->user->id)
            ->orderByDesc('created_at')->first();
        //发帖间隔时间30s
        if (!empty($threadFirst) && (time() - strtotime($threadFirst['created_at'])) < 30) {
            $this->outPut(ResponseCode::RESOURCE_EXIST, '发帖太快，稍后重试');
        }
    }
}
