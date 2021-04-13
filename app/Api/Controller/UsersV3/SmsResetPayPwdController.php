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
use App\Models\UserWalletFailLogs;
use App\Repositories\MobileCodeRepository;
use App\Validators\UserValidator;
use Discuz\Base\DzqController;
use Discuz\Contracts\Setting\SettingsRepository;
use Discuz\Qcloud\QcloudTrait;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Carbon;

class SmsResetPayPwdController extends AuthBaseController
{
    use QcloudTrait;

    const CODE_EXCEPTION = 5; //单位：分钟
    const CODE_INTERVAL = 60; //单位：秒

    protected $validation;
    protected $cache;
    protected $mobileCodeRepository;
    protected $settings;

    /**
     * @var MobileCode
     */
    private $mobileCode;
    /**
     * @var UserValidator
     */
    private $validator;

    public function __construct(
        ValidationFactory       $validation,
        MobileCode              $mobileCode,
        CacheRepository         $cache,
        MobileCodeRepository    $mobileCodeRepository,
        SettingsRepository      $settings,
        UserValidator           $validator
    ) {
        $this->validation           = $validation;
        $this->mobileCode           = $mobileCode;
        $this->cache                = $cache;
        $this->mobileCodeRepository = $mobileCodeRepository;
        $this->settings             = $settings;
        $this->validator            = $validator;
    }

    public function main()
    {
        $mobile                     = $this->inPut('mobile');
        $code                       = $this->inPut('code');
        $pay_password               = $this->inPut('pay_password');
        $pay_password_confirmation  = $this->inPut('pay_password_confirmation');

        $data = array();
        $data['mobile'] = $mobile;
        $data['code']   = $code;

        $this->validation->make($data, [
            'mobile'    => 'required',
            'code'      => 'required'
        ])->validate();

        /**
         * @var MobileCode $mobileCode
         **/
        $mobileCode = $this->mobileCodeRepository->getSmsCode($mobile, 'reset_pay_pwd');

        if (!$mobileCode || $mobileCode->code !== $code || $mobileCode->expired_at < Carbon::now()) {
            $this->outPut(ResponseCode::NET_ERROR, ResponseCode::$codeMap[ResponseCode::NET_ERROR]);
        }

        $mobileCode->changeState(MobileCode::USED_STATE);
        $mobileCode->save();

        if ($mobileCode->user && isset($pay_password)) {
            $this->validator->valid([
                                        'pay_password' => $pay_password,
                                        'pay_password_confirmation' => $pay_password_confirmation,
                                    ]);

            // 验证新密码与原密码不能相同
            if ($mobileCode->user->checkWalletPayPassword($pay_password)) {
                $this->outPut(ResponseCode::USER_UPDATE_ERROR,
                                    ResponseCode::$codeMap[ResponseCode::USER_UPDATE_ERROR]
                );
            }

            $mobileCode->user->changePayPassword($pay_password);
            $mobileCode->user->save();

            // 清空支付密码错误次数
            UserWalletFailLogs::deleteAll($mobileCode->user->id);
        }

        $this->outPut(ResponseCode::SUCCESS, '', $mobileCode->user);
    }
}
