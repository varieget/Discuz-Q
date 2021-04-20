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
use App\Models\MobileCode;
use Illuminate\Support\Carbon;

class SmsRebindController extends AuthBaseController
{
    public function main()
    {
        $mobileCode = $this->getMobileCode('rebind');

        if ($this->user->exists) {
            // 删除验证身份的验证码
            MobileCode::query() ->where('mobile', $this->user->getRawOriginal('mobile'))
                                ->where('type', 'verify')
                                ->where('state', 1)
                                ->where('updated_at', '<', Carbon::now()->addMinutes(10))
                                ->delete();

            $this->user->changeMobile($mobileCode->mobile);
            $this->user->save();

            $this->outPut(ResponseCode::SUCCESS, '', []);
        }

        $this->outPut(ResponseCode::NET_ERROR,ResponseCode::$codeMap[ResponseCode::NET_ERROR]);
    }
}
