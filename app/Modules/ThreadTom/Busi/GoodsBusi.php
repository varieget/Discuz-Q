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
use App\Models\PostGoods;

class GoodsBusi extends TomBaseBusi
{

    public function create()
    {
        $input = $this->verification();

        $postGoods = PostGoods::query()
            ->where('id',$input['goodsId'])
            ->where('user_id',$this->user['id'])
            ->get()
            ->toArray();

        if(empty($postGoods)){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND, ResponseCode::$codeMap[ResponseCode::RESOURCE_NOT_FOUND]);
        }
        return $this->jsonReturn($postGoods);
    }

    public function update()
    {
        return $this->create();
    }

    public function delete()
    {
        $goodsId = $this->getParams('goodsId');

        $threadTom = ThreadTom::query()
            ->where('id',$goodsId)
            ->update(['status'=>-1]);

        if ($threadTom) {
            return true;
        }

        return false;
    }

    public function verification()
    {
        $input = [
            'goodsId' => $this->getParams('goodsId'),
        ];
        $rules = [
            'goodsId' => 'required|int',
        ];
        $this->dzqValidate($input, $rules);

        return $input;
    }
}
