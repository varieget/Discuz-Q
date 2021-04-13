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

namespace App\Api\Controller\UsersV3;

use App\Common\ResponseCode;
use App\Models\SessionToken;
use Discuz\Base\DzqController;

class WechatPcBindPollController extends DzqController
{

    public function main()
    {
        $sessionToken = $this->inPut('session_token');
        $token = SessionToken::get($sessionToken);
        if (empty($token)) {
            // 二维码已失效，扫码超时
            $this->outPut(ResponseCode::PC_QRCODE_TIME_OUT, ResponseCode::$codeMap[ResponseCode::PC_QRCODE_TIME_OUT]);
        }

        if (is_null($token->payload)) {
            // 扫码中
            $this->outPut(ResponseCode::PC_QRCODE_SCANNING_CODE, ResponseCode::$codeMap[ResponseCode::PC_QRCODE_SCANNING_CODE]);
        }

        if (isset($token->payload['bind']) && $token->payload['bind']) {
            // 绑定成功
            $this->outPut(ResponseCode::SUCCESS, '', $token->payload);
        }

        $this->outPut(ResponseCode::PC_BIND_ERROR, ResponseCode::$codeMap[ResponseCode::PC_BIND_ERROR]);
    }
}
