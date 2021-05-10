<?php

/**
 * Copyright (C) 2020 Tencent Cloud.
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

namespace App\Api\Controller\RedPacketV3;

use App\Common\ResponseCode;
use App\Repositories\RedPacketRepository;
use Discuz\Base\DzqController;

class ResourceRedPacketController extends DzqController
{
    public $redPacket;

    public function __construct(RedPacketRepository $redPacket)
    {
        $this->redPacket = $redPacket;
    }

    public function main()
    {
        $id = $this->inPut('id');
        if(empty($id))       return  $this->outPut(ResponseCode::INVALID_PARAMETER );

        $build = $this->redPacket->findOrFail($id);
        $data = $this->camelData($build);

        return $this->outPut(ResponseCode::SUCCESS, '', $data);
    }
}
