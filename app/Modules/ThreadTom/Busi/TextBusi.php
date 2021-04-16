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

class TextBusi extends TomBaseBusi
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
        $textId = $this->getParams('textId');

        $threadTom = ThreadTom::query()
            ->where('id',$textId)
            ->update(['status'=>-1]);

        if ($threadTom) {
            return true;
        }

        return false;
    }

    public function verification(){
        $input = [
            'content' => $this->getParams('content'),
        ];
        $rules = [
            'content' => 'required|min:1|max:5000',
        ];
        $this->dzqValidate($input, $rules);

        return $input;
    }
}
