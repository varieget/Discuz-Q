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
use App\Repositories\MobileCodeRepository;
use Discuz\Base\DzqController;
use Discuz\Contracts\Setting\SettingsRepository;
use Discuz\Qcloud\QcloudTrait;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Carbon;

class SmsBindController extends DzqController
{
    use QcloudTrait;

    const CODE_EXCEPTION = 5; //单位：分钟
    const CODE_INTERVAL = 60; //单位：秒

    protected $validation;
    protected $cache;
    protected $mobileCodeRepository;
    protected $settings;
    protected $mobileCode;

    public function __construct(
        ValidationFactory       $validation,
        CacheRepository         $cache,
        MobileCodeRepository    $mobileCodeRepository,
        SettingsRepository      $settings,
        MobileCode              $mobileCode
    ) {
        $this->validation           = $validation;
        $this->cache                = $cache;
        $this->mobileCodeRepository = $mobileCodeRepository;
        $this->settings             = $settings;
        $this->mobileCode           = $mobileCode;
    }

    public function main()
    {
        $mobile = $this->inPut('mobile');
        $code   = $this->inPut('code');

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
        $mobileCode = $this->mobileCodeRepository->getSmsCode($mobile, 'bind');

        if (!$mobileCode || $mobileCode->code !== $code || $mobileCode->expired_at < Carbon::now()) {
            $this->outPut(ResponseCode::NET_ERROR, ResponseCode::$codeMap[ResponseCode::NET_ERROR]);
        }

        $mobileCode->changeState(MobileCode::USED_STATE);
        $mobileCode->save();

        // 判断手机号是否已经被绑定
        if ($this->user->mobile) {
            $this->outPut(ResponseCode::MOBILE_IS_ALREADY_BIND,
                                ResponseCode::$codeMap[ResponseCode::MOBILE_IS_ALREADY_BIND]
            );
        }

        if ($this->user->exists) {
            $this->user->changeMobile($mobileCode->mobile);
            $this->user->save();
            $this->mobileCode->user = $this->user;
        }

        $this->outPut(ResponseCode::SUCCESS, '', $this->mobileCode->user);
    }
}
