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

class DocBusi extends TomBaseBusi
{
    public function create()
    {
        $docIds = $this->getParams('docIds');
        if (count($docIds) > 9) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '文件不能超过9个');
        }
        return $this->jsonReturn(['docIds' => $docIds]);
    }

    public function update()
    {
        $docIds = $this->getParams('docIds');
        if (count($docIds) > 9) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '文件不能超过9个');
        }
        return $this->jsonReturn(['docIds' => $docIds]);
    }

    public function select()
    {
        $serializer = $this->app->make(AttachmentSerializer::class);
        $result = [];
        $docIds = $this->getParams('docIds');
        $attachments = $this->searchArray(PreQuery::THREAD_LIST_ATTACHMENTS, $docIds);
        if (!$attachments) {
            $attachments = Attachment::query()->whereIn('id', $docIds)->get();
        }
        foreach ($attachments as $attachment) {
            $thread = $this->searchArray(PreQuery::THREAD_LIST, $this->threadId);
            if ($thread) {
                $result[] = $this->camelData($serializer->getBeautyAttachment($attachment, $thread, $this->user));
            } else {
                $result[] = $this->camelData($serializer->getDefaultAttributes($attachment, $this->user));
            }
        }
        return $this->jsonReturn($result);
    }

}
