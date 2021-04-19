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

namespace App\Api\Controller\TradeV3;

use App\Commands\Trade\PayOrder;
use App\Common\ResponseCode;
use App\Models\Order;
use Discuz\Base\DzqController;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;

class PayOrderController extends DzqController
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    public function main()
    {
        try {
            // 兼容原来封装的逻辑
            $orderSn = $this->inPut("orderSn");
            $paymentType = $this->inPut("paymentType");
            $payPassword = $this->inPut("payPassword");
            if(empty($orderSn)){
                $this->outPut(ResponseCode::INVALID_PARAMETER);
            }
            if(empty($paymentType)){
                $this->outPut(ResponseCode::INVALID_PARAMETER);
            }
            $data = array(
                'order_sn'=>$orderSn,
                'payment_type'=>$paymentType
            );
            if(!empty($payPassword)){
                $data['pay_password'] = $payPassword;
            }
            $data = collect($data);
            $payOrder = $this->bus->dispatch(
                new PayOrder($orderSn, $this->user, $data)
            );
        } catch (\Exception $e) {
            $this->info('订单支付失败,订单id:' . $orderSn);
            $this->outPut(ResponseCode::INTERNAL_ERROR, $e->getMessage());
        }

        $result = [];
        $payOrderResult = $payOrder->payment_params;
        if ($paymentType == Order::PAYMENT_TYPE_WALLET) {
            $payOrderResult = $payOrderResult['wallet_pay'] ?? [];
            $result = [
                'id' => $payOrder->id,
                'desc' => $payOrder->body,
                'walletPayResult' => $this->camelData($payOrderResult),
                'wechatPayResult' => []
            ];
        } else {
            $payOrderResult['wechatQrcode'] = $payOrderResult['wechat_qrcode'] ?? '';
            $result = [
                'id' => $payOrder->id,
                'desc' => $payOrder->body,
                'walletPayResult' => [],
                'wechatPayResult' => $this->camelData($payOrderResult)
            ];
        }
        
        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }
}
