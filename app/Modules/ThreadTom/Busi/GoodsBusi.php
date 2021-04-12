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

use App\Modules\ThreadTom\TomBaseBusi;
use App\Models\ThreadTom;

class GoodsBusi extends TomBaseBusi
{

    public function create()
    {
        return $this->jsonReturn($this->verification());
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
            'platformId' => $this->getParams('platformId'),
            'title' => $this->getParams('title'),
            'imagePath' => $this->getParams('imagePath'),
            'price' => $this->getParams('price'),
            'type' => $this->getParams('type'),
            'typeName' => $this->getParams('typeName'),
            'status' => $this->getParams('status'),
            'readyContent' => $this->getParams('readyContent'),
            'detailContent' => $this->getParams('detailContent'),
        ];
        $rules = [
            'platformId' => 'required|int',
            'title' => 'required|max:200',
            'imagePath' => 'required|max:250',
            'price' => 'required:max:15',
            'type' => 'required|int',
            'typeName' => 'required|max:250',
            'status' => 'required|int',
            'readyContent' => 'required|max:250',
            'detailContent' => 'required|max:250',
        ];
        $this->dzqValidate($input, $rules);

        return $input;
    }
}
