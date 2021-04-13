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

class SmsRebindController extends AuthBaseController
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

    public function __construct(
        ValidationFactory       $validation,
        MobileCode              $mobileCode,
        CacheRepository         $cache,
        MobileCodeRepository    $mobileCodeRepository,
        SettingsRepository      $settings
    ) {
        $this->validation           = $validation;
        $this->mobileCode           = $mobileCode;
        $this->cache                = $cache;
        $this->mobileCodeRepository = $mobileCodeRepository;
        $this->settings             = $settings;
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
        $mobileCode = $this->mobileCodeRepository->getSmsCode($mobile, 'rebind');

        if (!$mobileCode || $mobileCode->code !== $code || $mobileCode->expired_at < Carbon::now()) {
            $this->outPut(ResponseCode::NET_ERROR, ResponseCode::$codeMap[ResponseCode::NET_ERROR]);
        }

        $mobileCode->changeState(MobileCode::USED_STATE);
        $mobileCode->save();

        if ($this->user->exists) {
            // 删除验证身份的验证码
            MobileCode::query()->where('mobile', $this->user->getRawOriginal('mobile'))
                ->where('type', 'verify')
                ->where('state', 1)
                ->where('updated_at', '<', Carbon::now()->addMinutes(10))
                ->delete();

            $this->user->changeMobile($mobileCode->mobile);
            $this->user->save();
            $this->mobileCode->user = $this->user;
        }

        $this->outPut(ResponseCode::SUCCESS, '', $this->mobileCode->user);
    }
}
