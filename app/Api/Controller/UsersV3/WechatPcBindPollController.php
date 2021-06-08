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
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;

class WechatPcBindPollController extends AuthBaseController
{
    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return true;
    }

    public function main()
    {
        try {
            $token = $this->getScanCodeToken();

            if (isset($token->payload['bind']) && $token->payload['bind']) {
                $result = $this->camelData($token->payload);
                $result = $this->addUserInfo($token->user, $result);
                // 绑定成功
                $this->outPut(ResponseCode::SUCCESS, '', $result);
            }

            $this->outPut(ResponseCode::PC_BIND_ERROR);
        } catch (\Exception $e) {
            app('errorLog')->info('requestId：' . $this->requestId . '-二维码异常-' . 'pc、H5轮询绑定接口异常-WechatPcBindPollController： 入参：'
                                  . 'sessionToken:'.$this->inPut('sessionToken') . ';userId:'. $this->user->id . ';异常：' . $e->getMessage());
            return $this->outPut(ResponseCode::INTERNAL_ERROR, 'pc、H5轮询绑定接口异常');
        }
    }
}
