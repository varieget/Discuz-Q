<?php

/**
 * Copyright (C) 2020 Tencent Cloud.
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

namespace App\Api\Controller\Random;

use App\Api\Serializer\TopicSerializer;
use App\Models\Topic;
use App\Repositories\TopicRepository;
use Discuz\Api\Controller\AbstractListController;
use Illuminate\Support\Collection;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Exception\InvalidParameterException;


class ListRandomTopicsController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = TopicSerializer::class;

    /**
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return Collection
     * @throws InvalidParameterException
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {

        $topicList = Topic::query()->orderBy('updated_at')->get();
        $topicList_array = json_decode($topicList,true);
        shuffle($topicList_array);
        if(count($topicList_array) <= 10){
            return $topicList;
        }else{
            $topicList_array = array_slice($topicList_array, 1, 10);
            $ids = array();
            foreach ($topicList_array as $key => $value) {
                $ids[] = $value['id'];
            }
            return Topic::query()->whereIn('id', $ids)->orderBy('updated_at')->get();
        }
    }
}
