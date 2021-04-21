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

namespace App\Api\Controller\ThreadsV3;


use App\Common\ResponseCode;
use App\Models\ThreadTom;
use App\Modules\ThreadTom\TomTrait;
use Discuz\Base\DzqController;

class UpdateTomController extends DzqController
{

    use TomTrait;

    public function main()
    {
        $threadId = $this->inPut('threadId');
        $content = $this->inPut('content');
        $tomJsons = $this->tomDispatcher($content,$this->UPDATE_FUNC);
        $db = $this->getDB();
        $db->beginTransaction();
        try {
            foreach ($tomJsons as $key => $value) {
                ThreadTom::query()
                    ->where(['thread_id' => $threadId, 'tom_type' => $value['tomId'], 'key' => $key, 'status' => ThreadTom::STATUS_ACTIVE])
                    ->update(['value' => json_encode($value['body'], 256)]);
            }
            $db->commit();
            $this->outPut(ResponseCode::SUCCESS);
        } catch (\Exception $e) {
            $db->rollBack();
            $this->outPut(ResponseCode::DB_ERROR, $e->getMessage());
        }
    }
}
