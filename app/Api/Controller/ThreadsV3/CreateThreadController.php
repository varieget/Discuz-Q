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
use App\Models\ThreadTom;
use App\Modules\ThreadTom\TomTrait;
use Discuz\Base\DzqController;

class CreateThreadController extends DzqController
{
    use TomTrait;

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
        $thread = new Thread();
        $userId = $this->user->id;
        $categoryId = $this->inPut('categoryId');
        $title = $this->inPut('title');//title没有则自动生成
        $price = $this->inPut('price');
        $attachmentPrice = $this->inPut('attachmentPrice');
        $freeWords = $this->inPut('freeWords');
        $position = $this->inPut('position');
        $isAnonymous = $this->inPut('anonymous');
        $dataThread = [
            'user_id' => $userId,
            'category_id' => $categoryId,
            'title' => $title,
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
        $threadId = $thread->id;

        //插入post数据
        $text = $content['text'];
        $post = new Post();
        list($ip, $port) = $this->getIpPort();
        $dataPost = [
            'user_id' => $userId,
            'thread_id' => $threadId,
            'content' => $text,
            'ip' => $ip,
            'port' => $port,
            'is_first' => Post::FIRST_YES,
            'is_approved' => Post::APPROVED
        ];
        $post->setRawAttributes($dataPost);
        $post->save();
        //插入tom数据
        $indexes = $content['indexes'];
        $attrs = [];
        $tomJsons = $this->tomDispatcher($indexes, null, $threadId);
        $tags = [];
        foreach ($tomJsons as $key => $value) {
            $attrs[] = [
                'thread_id' => $threadId,
                'tom_type' => $value['tomId'],
                'key' => $key,
                'value' => json_encode($value['body'], 256)
            ];
            $tags[] = [
                'thread_id' => $threadId,
                'tag' => $value['tomId']
            ];
        }
        ThreadTom::query()->insert($attrs);
        //添加tag类型
        ThreadTag::query()->insert($tags);

        return $this->getResult($thread, $post, $tomJsons);
    }

    private function getResult($thread, $post, $tomJsons)
    {

        $linkString = $thread['title'] . $post['content'];
        list($search, $replace) = Thread::instance()->getReplaceString($linkString);
        $content = [
            'text' => str_replace($search, $replace, $post['content']),
            'indexes' => $this->tomDispatcher($tomJsons, $this->SELECT_FUNC)
        ];
        return [
            'threadId' => $thread['id'],
            'userId' => $thread['user_id'],
            'categoryId' => $thread['category_id'],
            'title' => str_replace($search, $replace, $thread['title']),
            'price' => $thread['price'],
            'attachmentPrice' => $thread['attachment_price'],
            'position' => [
                'longitude' => $thread['longitude'],
                'latitude' => $thread['latitude'],
                'address' => $thread['address'],
                'location' => $thread['location']
            ],
            'isAnonymous' => $thread['is_anonymous'],
            'content' => $content
        ];
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
