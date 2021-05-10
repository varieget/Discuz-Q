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


use App\Models\ThreadVideo;
use App\Modules\ThreadTom\PreQuery;
use App\Modules\ThreadTom\TomBaseBusi;

class VideoBusi extends TomBaseBusi
{

    public function create()
    {
        $videoId = $this->getParams('videoId');
        return $this->jsonReturn(['videoId' => $videoId]);
    }

    public function update()
    {
        $videoId = $this->getParams('videoId');
        return $this->jsonReturn(['videoId' => $videoId]);
    }

    public function select()
    {
        $videoId = $this->getParams('videoId');
        $video = $this->searchArray(PreQuery::THREAD_LIST_VIDEO, $videoId, $hasKey);
        if (!$hasKey) {
            $video = ThreadVideo::instance()->getThreadVideoById($videoId);
        } else {
            $video = ThreadVideo::instance()->getThreadVideoById($videoId, $video);
        }
        return $this->jsonReturn($video);
    }
}
