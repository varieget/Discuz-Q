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

use App\Common\ResponseCode;
use App\Modules\ThreadTom\TomBaseBusi;
use App\Models\ThreadTom;
use App\Models\ThreadVideo;

class VoiceBusi extends TomBaseBusi
{

    private $voiceCount = 1;

    public function create()
    {
        $input = $this->verification();

        $threadVideo = ThreadVideo::query()
            ->whereIn('id',$input['voiceIds'])
            ->where('user_id',$this->user['id'])
            ->where('type',ThreadVideo::TYPE_OF_AUDIO)
            ->get()
            ->toArray();

        if(empty($threadVideo)){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND, ResponseCode::$codeMap[ResponseCode::RESOURCE_NOT_FOUND]);
        }

        return $this->jsonReturn($threadVideo);
    }

    public function update()
    {
        return $this->create();
    }

    public function delete()
    {
        $voiceId = $this->getParams('voiceId');

        $threadTom = ThreadTom::query()
            ->where('id',$voiceId)
            ->update(['status'=>-1]);

        if ($threadTom) {
            return true;
        }

        return false;
    }

    public function verification()
    {
        $input = [
            'voiceIds' => $this->getParams('voiceIds'),
        ];
        $rules = [
            'voiceIds' => 'required|array|min:1|max:'.$this->voiceCount,
        ];
        $this->dzqValidate($input, $rules);

        return $input;
    }
}
