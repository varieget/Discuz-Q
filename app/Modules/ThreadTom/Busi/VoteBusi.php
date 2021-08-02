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
use App\Common\CacheKey;
use App\Models\ThreadVote;
use Discuz\Base\DzqCache;
use App\Common\ResponseCode;
use App\Models\Attachment;
use App\Models\Thread;
use App\Modules\ThreadTom\TomBaseBusi;

class VoteBusi extends TomBaseBusi
{
    public function create()
    {
        $voteIds = $this->getParams('voteIds');
        if (count($voteIds) > 1) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '投票不能超过1个');
        }
        return $this->jsonReturn(['voteIds' => $voteIds]);
    }

    public function update()
    {
        $voteIds = $this->getParams('voteIds');
        if (count($voteIds) > 1) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '投票不能超过1个');
        }
        return $this->jsonReturn(['voteIds' => $voteIds]);
    }

    public function select()
    {
//        $thread_vote = ThreadVote::query()



        return $this->jsonReturn($result);
    }
}
