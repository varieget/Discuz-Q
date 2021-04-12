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
use App\User\Bind;
use App\Validators\UserValidator;
use Discuz\Base\DzqController;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class SmsVerifyController extends DzqController
{
    protected $mobileCodeRepository;
    protected $bus;
    protected $validation;

    /**
     * @var MobileCode
     */
    private $mobileCode;
    /**
     * @var SettingsRepository
     */
    private $settings;
    /**
     * @var Bind
     */
    private $bind;
    /**
     * @var Events
     */
    private $events;
    /**
     * @var UserValidator
     */
    private $validator;

    public function __construct(
        MobileCodeRepository    $mobileCodeRepository,
        Dispatcher              $bus,
        Factory                 $validation,
        MobileCode              $mobileCode,
        SettingsRepository      $settings,
        Bind                    $bind,
        Events                  $events,
        UserValidator           $validator
    ){
        $this->mobileCodeRepository = $mobileCodeRepository;
        $this->bus                  = $bus;
        $this->validation           = $validation;
        $this->mobileCode           = $mobileCode;
        $this->settings             = $settings;
        $this->bind                 = $bind;
        $this->events               = $events;
        $this->validator            = $validator;
    }

    public function main()
    {
        $mobile = $this->inPut('mobile');
        $code   = $this->inPut('code');

        $data = array();
        $data['mobile'] = $mobile;
        $data['code']   = $code;
        $data['ip']     = ip($this->request->getServerParams());
        $data['port']   = Arr::get($this->request->getServerParams(), 'REMOTE_PORT', 0);

        $this->validation->make($data, [
            'mobile'    => 'required',
            'code'      => 'required'
        ])->validate();

        /**
         * @var MobileCode $mobileCode
         **/
        $mobileCode = $this->mobileCodeRepository->getSmsCode($mobile, 'verify');

        if (!$mobileCode || $mobileCode->code !== $code || $mobileCode->expired_at < Carbon::now()) {
            $this->outPut(ResponseCode::NET_ERROR, ResponseCode::$codeMap[ResponseCode::NET_ERROR]);
        }

        $mobileCode->changeState(MobileCode::USED_STATE);
        $mobileCode->save();

        $this->outPut(ResponseCode::SUCCESS, '', $mobileCode->user);
    }
}
