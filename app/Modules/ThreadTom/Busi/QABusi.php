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

class QABusi extends TomBaseBusi
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
            'threadId' => $this->getParams('thread_id'),
            'postId' => $this->getParams('post_id'),
            'type' => $this->getParams('type'),
            'userId' => $this->getParams('user_id'),
            'answerId' => $this->getParams('answer_id'),
            'money' => $this->getParams('money'),
            'remainMoney' => $this->getParams('remain_money'),
            'createdAt' => $this->getParams('created_at'),
            'updatedAt' => $this->getParams('updatedAt'),
            'expiredAt' => $this->getParams('expired_at'),
        ];
        $rules = [
            'threadId' => 'required|int',
            'postId' => 'required|int',
            'type' => 'required|int',
            'userId' => 'required|int',
            'answerId' => 'required|int',
            'money' => 'required',
            'remainMoney' => 'required',
            'createdAt' => 'date',
            'updatedAt' => 'date',
            'expiredAt' => 'required|date',
        ];
        $this->dzqValidate($input, $rules);

        return $input;
    }
}
