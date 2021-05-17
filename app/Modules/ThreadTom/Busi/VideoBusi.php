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


use App\Common\CacheKey;
use App\Common\DzqCache;
use App\Models\ThreadVideo;
use App\Modules\ThreadTom\TomBaseBusi;

class VideoBusi extends TomBaseBusi
{

    public function create()
    {
        $videoId = $this->getParams('videoId');
        $video = ThreadVideo::query()->where('id', $videoId)->first();
        if (!empty($video) && !empty($this->threadId)) {
            $video->thread_id = $this->threadId;
            $video->save();
        }
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

        $videos = DzqCache::extractCacheArrayData(CacheKey::LIST_THREADS_V3_VIDEO, $videoId, function ($videoId) {
            $videos = ThreadVideo::query()->where(['file_id' => $videoId, 'status' => ThreadVideo::VIDEO_STATUS_SUCCESS])->get()->toArray();
            if (empty($videos)) {
                $videos = [$videoId => null];
            } else {
                $videos = [$videoId => current($videos)];
            }
            return $videos;
        });
        if(empty($videos)){
            $videos = ThreadVideo::query()->where(['file_id' => $videoId, 'status' => ThreadVideo::VIDEO_STATUS_SUCCESS])->get()->toArray();
            if (empty($videos)) {
                $videos = [$videoId => null];
            } else {
                $videos = [$videoId => current($videos)];
            }
        }

        $video = $videos[$videoId] ?? null;
        if ($video) {
            $video = ThreadVideo::instance()->threadVideoResult($video);
            if (!$this->canViewTom) {
                $video['mediaUrl'] = '';
            }
        } else {
            $video = false;
        }
        return $this->jsonReturn($video);
    }
}
