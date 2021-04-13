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

class RedPackBusi extends TomBaseBusi
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

    public function verification(){
        $input = [
            'threadId' => $this->getParams('threadId'),
            'postId' => $this->getParams('postId'),
            'rule' => $this->getParams('rule'),
            'condition' => $this->getParams('condition'),
            'likenum' => $this->getParams('likenum'),
            'money' => $this->getParams('money'),
            'number' => $this->getParams('number'),
            'remainMoney' => $this->getParams('remainMoney'),
            'remainNumber' => $this->getParams('remainNumber'),
            'status' => $this->getParams('status'),
            'createdAt' => $this->getParams('createdAt'),
            'updatedAt' => $this->getParams('updatedAt'),
        ];
        $rules = [
            'threadId' => 'required|int',
            'postId' => 'required|int',
            'rule' => 'required|int',
            'condition' => 'required|int',
            'likenum' => 'required|int',
            'money' => 'required',
            'number' => 'required|int',
            'remainMoney' => 'required',
            'remainNumber' => 'required|int',
            'status' => 'required|int',
            'createdAt' => 'date',
            'updatedAt' => 'date'
        ];
        $this->dzqValidate($input, $rules);

        return $input;
    }
}
