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
        $input = $this->verification();

        return $this->jsonReturn($input);
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
            'content' => $this->getParams('content'),
            'summaryText' => $this->getParams('summaryText'),
            'summary' => $this->getParams('summary'),
            'parseContentHtml' => $this->getParams('parseContentHtml'),
            'contentHtml' => $this->getParams('contentHtml'),
            'createdAt' => $this->getParams('createdAt'),
            'updatedAt' => $this->getParams('updatedAt'),
        ];
        $rules = [
            'content' => 'required|max:5000',
            'summaryText' => 'required|max:5000',
            'summary' => 'required|max:5000',
            'parseContentHtml' => 'required|max:5000',
            'contentHtml' => 'required|max:5000',
            'createdAt' => 'date',
            'updatedAt' => 'date',
        ];
        $this->dzqValidate($input, $rules);

        return $input;
    }
}
