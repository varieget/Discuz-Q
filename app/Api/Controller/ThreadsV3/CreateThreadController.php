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
use App\Models\ThreadHot;
use App\Models\ThreadTom;
use App\Models\ThreadText;
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
        $title = $this->inPut('title');
        $content = $this->inPut('content');
        $position = $this->inPut('position');
        $isAnonymous = $this->inPut('anonymous');//非必须
        $summary = $this->inPut('summary');//非必须
        if (!$this->canCreateThread($this->user, $categoryId)) {
            $this->outPut(ResponseCode::UNAUTHORIZED);
        }
        !empty($position) && $this->dzqValidate($position, [
            'longitude' => 'required',
            'latitude' => 'required',
            'address' => 'required',
            'location' => 'required'
        ]);
        $params = [
            'categoryId' => $categoryId,
            'title' => $title,
            'content' => $content,
            'position' => $position,
            'isAnonymous' => $isAnonymous,
            'summary' => $summary
        ];
        $this->createThread($content, $params);
        $this->outPut(ResponseCode::SUCCESS);
    }


    /**
     * @desc 发布一个新帖子
     * @param $content
     * @param $params
     * @return bool
     */
    private function createThread($content, $params)
    {
        $db = $this->getDB();
        $db->beginTransaction();
        try {
            $this->executeEloquent($content, $params);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            $this->info('createThread_error_' . $this->user->id, $e->getMessage());
            $this->outPut(ResponseCode::DB_ERROR, $e->getMessage());
        }
    }

    private function executeEloquent($content, $params)
    {
        $text = $content['text'];
        //插入text数据
        $tText = new ThreadText();
        list($ip, $port) = $this->getIpPort();
        $data = [
            'user_id' => $this->user->id,
            'category_id' => $params['categoryId'],
            'title' => $params['title'],
            'summary' => empty($params['summary']) ? $tText->getSummary($text) : $params['summary'],
            'text' => $text,
            'status' => ThreadText::STATUS_ACTIVE,
            'ip' => $ip,
            'port' => $port
        ];
        if (!empty($params['position'])) {
            $data['longitude'] = $params['position']['longitude'];
            $data['latitude'] = $params['position']['latitude'];
            $data['address'] = $params['position']['address'];
            $data['location'] = $params['position']['location'];
        }
        $tText->setRawAttributes($data);
        $tText->save();
        $threadId = $tText->id;
        //插入hot数据
        $tHot = new ThreadHot();
        $tHot->thread_id = $threadId;
        $tHot->save();
        //插入tom数据
        $attrs = [];
        $tomJsons = $this->tomDispatcher($content,null,$threadId);
        foreach ($tomJsons as $key => $value) {
            $attrs[] = [
                'thread_id' => $threadId,
                'tom_type' => $value['tomId'],
                'key' => $key,
                'value' => json_encode($value['body'], 256)
            ];
        }
        ThreadTom::query()->insert($attrs);
    }

    private function limitCreateThread()
    {
        $threadFirst = ThreadText::query()
            ->select(['id', 'user_id', 'category_id', 'created_at'])
            ->where('user_id', $this->user->id)
            ->orderByDesc('created_at')->first();
        //发帖间隔时间30s
        if (!empty($threadFirst) && (time() - strtotime($threadFirst['created_at'])) < 30) {
            $this->outPut(ResponseCode::RESOURCE_EXIST, '发帖太快，稍后重试');
        }
    }

}
