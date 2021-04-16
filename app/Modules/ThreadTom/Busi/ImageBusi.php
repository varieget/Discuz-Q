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
use App\Models\Attachment;
use App\Models\ThreadTom;

class ImageBusi extends TomBaseBusi
{

    private $imageCount = 9;

    public function create()
    {
        $input = $this->verification();

        $attachment = Attachment::query()
            ->whereIn('id',$input['imageIds'])
            ->where('user_id',$this->user['id'])
            ->where('type',Attachment::TYPE_OF_IMAGE)
            ->get()
            ->toArray();

        if(empty($attachment) || count($input['imageIds']) != count($attachment)){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND, ResponseCode::$codeMap[ResponseCode::RESOURCE_NOT_FOUND]);
        }

        return $this->jsonReturn($attachment);
    }

    public function update()
    {
        return $this->create();
    }

    public function delete()
    {
        $imageId = $this->getParams('imageId');

        $threadTom = ThreadTom::query()
            ->where('id',$imageId)
            ->update(['status'=>-1]);

        if ($threadTom) {
            return true;
        }
        
        return false;
    }

    public function verification()
    {
        $input = [
            'imageIds' => $this->getParams('imageIds'),
        ];
        $rules = [
            'imageIds' => 'required|array|min:1|max:'.$this->imageCount,
        ];
        $this->dzqValidate($input, $rules);

        return $input;
    }
}
