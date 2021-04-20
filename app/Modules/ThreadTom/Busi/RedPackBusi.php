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
use App\Models\RedPacket;
use App\Models\Order;
use App\Models\ThreadTom;
use App\Models\ThreadRedPacket;

class RedPackBusi extends TomBaseBusi
{

    public function create()
    {
        $input = $this->verification();
        if($input['price']*100 <  $input['number']){
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
            $this->outPut(ResponseCode::INTERNAL_ERROR, ResponseCode::$codeMap[ResponseCode::INTERNAL_ERROR]);
        }

        $threadRedPacket = new ThreadRedPacket;
        $threadRedPacket->thread_id = $this->threadId;
        $threadRedPacket->post_id = 0;
        $threadRedPacket->rule = $input['rule'];
        $threadRedPacket->condition = $input['condition'];
        $threadRedPacket->likenum = $input['likenum'];
        $threadRedPacket->money = $input['price'];
        $threadRedPacket->number = $input['number'];
        $threadRedPacket->status = RedPacket::RED_PACKET_STATUS_VALID;
        $threadRedPacket->save();

        $threadRedPacket->isSelect = false;
        $threadRedPacket->content = $input['content'];

        return $this->jsonReturn($threadRedPacket);
    }

    public function select()
    {
        if (isset($this->body['isSelect'])) {
            return $this->jsonReturn($this->body);
        }

        $redPacket = ThreadRedPacket::query()->where('id',$this->body['id'])->first(['remain_money','remain_number','status']);
        $this->body['remain_money'] = $redPacket['remain_money'];
        $this->body['remain_number'] = $redPacket['remain_number'];
        $this->body['status'] = $redPacket['remain_number'];

        return $this->jsonReturn($this->body);

    }

    public function verification(){
        $input = [
            'condition' => $this->getParams('condition'),
            'likenum' => $this->getParams('condition')==1 ? $this->getParams('likenum') : 0,
            'number' => $this->getParams('number'),
            'rule' => $this->getParams('rule'),
            'orderId' => $this->getParams('orderId'),
            'price' => $this->getParams('price'),
            'content' => $this->getParams('type')
        ];
        $rules = [
            'condition' => 'required|integer|in:0,1',
            'likenum' => $input['condition']==1 ? 'required|int|min:1|max:250' : '',
            'number' => 'required|int|min:1|max:100',
            'rule' => 'required|integer|in:0,1',
            'orderId' => 'required|numeric',
            'price' => 'required|numeric|min:0.01|max:200',
            'content' => 'max:1000',
        ];

        $this->dzqValidate($input, $rules);

        return $input;
    }
}
