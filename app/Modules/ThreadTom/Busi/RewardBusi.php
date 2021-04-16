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

namespace App\Modules\ThreadTom\Busi;

use App\Common\ResponseCode;
use App\Modules\ThreadTom\TomBaseBusi;
use App\Models\Order;
use App\Models\ThreadReward;
use App\Models\ThreadTom;

class RewardBusi extends TomBaseBusi
{
    public function create()
    {
        $input = $this->verification();

        if(strtotime($input['expiredAt']) < time()+24*60*60){
            $this->outPut(ResponseCode::INVALID_PARAMETER, ResponseCode::$codeMap[ResponseCode::INVALID_PARAMETER]);
        }

        $order = Order::query()
            ->where('order_sn',$input['orderId'])
            ->first(['id','thread_id','user_id','status','amount','expired_at']);

        if (!empty($order['thread_id']) ||
            $order['user_id'] != $this->user['id'] ||
            $order['status'] != Order::ORDER_STATUS_PAID ||
            strtotime($order['expired_at']) < time()||
            $order['amount'] != $input['price']) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, ResponseCode::$codeMap[ResponseCode::INVALID_PARAMETER]);
        }

        $order->thread_id = $this->threadId;
        $order->save();

        if (empty($order['thread_id'])) {
            $this->outPut(ResponseCode::NOT_FOUND_USER, ResponseCode::$codeMap[ResponseCode::NOT_FOUND_USER]);
        }

        $threadReward = new ThreadReward;
        $threadReward->thread_id = $this->threadId;
        $threadReward->type = $input['type'];
        $threadReward->user_id = $this->user['id'];
        $threadReward->money = $input['price'];
        $threadReward->expired_at = $input['expiredAt'];
        $threadReward->save();

        return $this->jsonReturn($threadReward);
    }

    public function delete()
    {
        $rewardId = $this->getParams('rewardId');

        $threadTom = ThreadTom::query()
            ->where('id',$rewardId)
            ->update(['status'=>-1]);

        if ($threadTom) {
            return true;
        }

        return false;
    }

    public function verification(){
        $input = [
            'orderId' => $this->getParams('orderId'),
            'price' => $this->getParams('price'),
            'type' => $this->getParams('type'),
            'expiredAt' => $this->getParams('expiredAt'),
        ];
        $rules = [
            'orderId' => 'required|numeric',
            'price' => 'required|numeric|min:0.01',
            'type' => 'required|integer|in:0,1',
            'expiredAt' => 'required|date'
        ];
        $this->dzqValidate($input, $rules);

        return $input;
    }
}
