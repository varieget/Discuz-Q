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

    public function create()
    {
        $input = $this->verification();

        $attachment = Attachment::query()
            ->whereIn('id',$input['imageIds'])
            ->where('type',Attachment::TYPE_OF_IMAGE)
            ->get()
            ->toArray();

        if(empty($attachment)){
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
        $deleteId = $this->getParams('deleteId');

        $threadTom = ThreadTom::query()
            ->where('id',$deleteId)
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
            'imageIds' => 'required|array',
        ];
        $this->dzqValidate($input, $rules);

        return $input;
    }
}
