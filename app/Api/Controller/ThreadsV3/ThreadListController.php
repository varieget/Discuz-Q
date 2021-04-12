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
use App\Modules\ThreadTom\TomTrait;
use Discuz\Base\DzqController;

class ThreadListController extends DzqController
{

    use TomTrait;

    public function main()
    {
        $filter = $this->inPut('filter');
        $currentPage = $this->inPut('page');
        $perPage = $this->inPut('perPage');

        $categoryId = $this->inPut('categoryId');
        if (!$this->canViewThread($this->user, $categoryId)) {
            $this->outPut(ResponseCode::UNAUTHORIZED);
        }

        $categoryIds = Category::instance()->getValidCategoryIds($this->user,$categoryId);

        $threadTexts = $this->filterThreadTexts($filter, $currentPage, $perPage);

    }
    function getFilterThreads($filter, $currentPage, $perPage){
        if (empty($filter)) $filter = [];
        $this->dzqValidate($filter, [
            'sticky' => 'integer|in:0,1',
            'essence' => 'integer|in:0,1',
            'types' => 'array',
            'categoryids' => 'array',
            'sort' => 'integer|in:1,2,3',
            'attention' => 'integer|in:0,1',
        ]);
        $stick = 0;
        $essence = null;
        $types = [];
        $categoryids = [];
        $sort = 1;
        $attention = 0;
        isset($filter['sticky']) && $stick = $filter['sticky'];
        isset($filter['essence']) && $essence = $filter['essence'];
        isset($filter['types']) && $types = $filter['types'];
        isset($filter['categoryids']) && $categoryids = $filter['categoryids'];
        isset($filter['sort']) && $sort = $filter['sort'];
        isset($filter['attention']) && $attention = $filter['attention'];
        $categoryids = Category::instance()->getValidCategoryIds($this->user, $categoryids);
        if (!$categoryids) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '没有浏览权限');
        }
        $threads = ThreadText::query()
            ->where(['is_stick'=>ThreadText::FIELD_NO,'status'=>ThreadText::STATUS_ACTIVE]);
        !empty($essence) && $threads = $threads->where('is_essence', $essence);

        if(empty($types)){
//            $threads = $threads->leftJoin('thread_tag','thread_text','=',)

        }






    }
}
