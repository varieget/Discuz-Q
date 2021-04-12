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
use App\Models\ThreadText;
use Discuz\Base\DzqController;

class ThreadStickController extends DzqController
{

    public function main()
    {
        $categoryIds = $this->inPut('categoryIds');
        $validCategories = Category::instance()->getValidCategoryIds($this->user, $categoryIds);
        if (!$validCategories) {
            $this->outPut(ResponseCode::UNAUTHORIZED);
        }
        $threadText = ThreadText::query()
            ->select(['id', 'user_id', 'title', 'summary'])
            ->where(['status' => ThreadText::STATUS_ACTIVE, 'is_sticky' => ThreadText::FIELD_YES])
            ->whereIn('category_id', $validCategories)
            ->get()
            ->toArray();

        $result = [];
        foreach ($threadText as $item) {
            $result[] = [
                'pid' => $item['id'],
                'userId' => $item['user_id'],
                'title' => $item['title']
            ];
        }
        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }
}
