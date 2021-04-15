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
use Illuminate\Support\Arr;

class SmsVerifyController extends AuthBaseController
{
    public function main()
    {
        $mobile = $this->inPut('mobile');
        $code   = $this->inPut('code');

        $data = array();
        $data['mobile'] = $mobile;
        $data['code']   = $code;
        $data['ip']     = ip($this->request->getServerParams());
        $data['port']   = Arr::get($this->request->getServerParams(), 'REMOTE_PORT', 0);

        $this->dzqValidate($data, [
            'mobile'    => 'required',
            'code'      => 'required'
        ]);

        $mobileCode = $this->changeMobileCodeState($mobile, 'verify', $code);

        if ($mobileCode->user->exists) {
            $this->outPut(ResponseCode::SUCCESS, '', $this->camelData($mobileCode->user));
        }

        $this->outPut(ResponseCode::NET_ERROR,ResponseCode::$codeMap[ResponseCode::NET_ERROR]);
    }
}
