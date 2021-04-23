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

namespace App\Modules\ThreadTom\Busi;

use App\Api\Serializer\AttachmentSerializer;
use App\Common\ResponseCode;
use App\Models\Attachment;
use App\Modules\ThreadTom\PreQuery;
use App\Modules\ThreadTom\TomBaseBusi;

class ImageBusi extends TomBaseBusi
{
    public function create()
    {
        $imageIds = $this->getParams('imageIds');
        if(count($imageIds)>9){
            $this->outPut(ResponseCode::INVALID_PARAMETER,'图片数量不能超过9张');
        }
        return $this->jsonReturn(['imageIds'=>$imageIds]);
    }

    public function update()
    {
        $imageIds = $this->getParams('imageIds');
        if(count($imageIds)>9){
            $this->outPut(ResponseCode::INVALID_PARAMETER,'图片数量不能超过9张');
        }
        return $this->jsonReturn(['imageIds'=>$imageIds]);
    }

    public function select()
    {
        $serializer = $this->app->make(AttachmentSerializer::class);
        $result = [];
        $imageIds = $this->getParams('imageIds');
        $attachments = $this->searchArray(PreQuery::THREAD_LIST_ATTACHMENTS,$imageIds);
        if (!$attachments) {
            $attachments = Attachment::query()->whereIn('id', $imageIds)->get();
        }
        foreach ($attachments as $attachment) {
            $result[] = $this->camelData($serializer->getDefaultAttributes($attachment, $this->user));
            $result[] = $this->camelData($serializer->getDefaultAttributes($attachment, $this->user));
        }
        return $this->jsonReturn($result);
    }
}
