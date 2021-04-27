<?php
/**
 * Copyright (C) 2021 Tencent Cloud.
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

namespace App\Api\Controller\WalletV3;


use App\Commands\Wallet\CreateUserWalletCash;
use App\Common\ResponseCode;
use App\Common\Utils;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Base\DzqController;
use Illuminate\Contracts\Bus\Dispatcher;

class CreateUserWalletCashController extends DzqController
{
    use AssertPermissionTrait;

    public $bus;


    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }


    public function main()
    {
        $data = [
            'cash_apply_amount' => $this->inPut('cashApplyAmount'),
            'cash_mobile' => $this->inPut('cashMobile'),
            'cash_type' => $this->inPut('cashType')
        ];

        if(empty($data['cash_apply_amount'])){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }

        if(empty($data['cash_mobile'])){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }

        $data = collect(Utils::arrayKeysToSnake($data));
        $result = $this->bus->dispatch(
            new CreateUserWalletCash($this->user, $data)
        );

        $wallet =  $this->camelData($result);

        if($result['user_id'] = $this->user->id){
            return $this->outPut(ResponseCode::SUCCESS,'',$wallet);
        }else{
            return $this->outPut(ResponseCode::NET_ERROR);
        }
    }


}
