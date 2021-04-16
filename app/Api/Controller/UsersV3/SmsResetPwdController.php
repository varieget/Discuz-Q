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
use App\Validators\UserValidator;

class SmsResetPwdController extends AuthBaseController
{
    protected $validator;

    public function __construct(UserValidator $validator) {
        $this->validator = $validator;
    }

    public function main()
    {
        $param              = $this->getSmsParam('reset_pwd');
        $mobileCode         = $param['mobileCode'];
        $data['password']   = $this->inPut('password');
        $password           = $data['password'];

        $this->dzqValidate($data, [
            'password'  => 'required'
        ]);

        if ($mobileCode->user && isset($password)) {
            $this->validator->valid([
                'password' => $password
            ]);

            // 验证新密码与原密码不能相同
            if ($mobileCode->user->checkPassword($password)) {
                $this->outPut(ResponseCode::USER_UPDATE_ERROR,
                              ResponseCode::$codeMap[ResponseCode::USER_UPDATE_ERROR]
                );
            }

            $mobileCode->user->changePassword($password);
            $mobileCode->user->save();

            $this->outPut(ResponseCode::SUCCESS, '', []);
        }

        $this->outPut(ResponseCode::NET_ERROR,ResponseCode::$codeMap[ResponseCode::NET_ERROR]);
    }
}
