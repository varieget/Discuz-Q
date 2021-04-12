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
use App\Models\ThreadText;
use App\Models\ThreadTom;
use App\Modules\ThreadTom\TomTrait;
use Discuz\Base\DzqController;

class ThreadDetailController extends DzqController
{

    use TomTrait;

    public function main()
    {
        $threadId = $this->inPut('threadId');
        $threadText = ThreadText::query()
            ->where(['id' => $threadId, 'status' => ThreadText::STATUS_ACTIVE])->first();
        $threadHot = ThreadHot::query()
            ->where('thread_id', $threadId)->first();
        if (empty($threadText) || empty($threadHot)) {
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
        }
        if (!$this->canViewThreadDetail($this->user, $threadText['category_id'])) {
            $this->outPut(ResponseCode::UNAUTHORIZED);
        }
        $result = [
            'userId' => $threadText['user_id'],
            'categoryId' => $threadText['category_id'],
            'title' => $threadText['title'],
            'summary' => $threadText['summary'],
            'position' => [
                'longitude' => $threadText['longitude'],
                'latitude' => $threadText['latitude'],
                'address' => $threadText['address'],
                'location' => $threadText['location']
            ],
            'isSticky' => $threadText['is_sticky'],
            'isEssence' => $threadText['is_essence'],
            'isAnonymous' => $threadText['is_anonymous'],
            'isSite' => $threadText['is_site'],
            'hotData' => $this->getHotData($threadHot),
            'content' => $this->getContent($threadId, $threadText)
        ];
        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }

    private function getContent($threadId, $threadText)
    {
        $threadTom = ThreadTom::query()
            ->where([
                'thread_id' => $threadId,
                'status' => ThreadTom::STATUS_ACTIVE
            ])->get()->toArray();
        $tomContent = [];
        foreach ($threadTom as $item) {
            $tomContent[$item['key']] = [
                'tomId' => $item['tom_type'],
                'operation' => $this->SELECT_FUNC,
                'body' => json_decode($item['value'], true)
            ];
        }
        $tomJsons = $this->tomDispatcher($tomContent, $this->SELECT_FUNC);
        $content = ['text' => $threadText['text']];
        $content += $tomJsons;
        return $content;
    }

    private function getHotData($threadHot)
    {
        return [
            'commentCount' => $threadHot['comment_count'],
            'viewCount' => $threadHot['view_count'],
            'shareCount' => $threadHot['share_count'],
            'rewardCount' => $threadHot['reward_count'],
            'payCount' => $threadHot['pay_count'],
            'lastPostTime' => $threadHot['last_post_time'],
            'lastPostUser' => $threadHot['last_post_user']
        ];
    }
}
