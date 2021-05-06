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
use App\Models\OrderChildren;
use App\Models\ThreadReward;
use App\Models\ThreadTom;

class RewardBusi extends TomBaseBusi
{
    public function create()
    {
        $input = $this->verification();

        if(strtotime($input['expiredAt']) < time()+24*60*60){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }

        $order = Order::query()
            ->where('order_sn',$input['orderSn'])
            ->first(['id','thread_id','user_id','status','amount','expired_at','type']);
        if (empty($order) ||
            !empty($order['thread_id']) ||
            ($order->type == Order::ORDER_TYPE_QUESTION_REWARD && !empty($order['thread_id'])) ||
            $order['user_id'] != $this->user['id'] || 
            $order['status'] != Order::ORDER_STATUS_PAID || 
            (!empty($order['expired_at']) && strtotime($order['expired_at']) < time())|| 
            ($order->type == Order::ORDER_TYPE_QUESTION_REWARD && $order->amount != $input['price'])) {
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }

        if ($order->type == Order::ORDER_TYPE_MERGE) {
            $orderChildrenInfo = OrderChildren::query()
                ->where('order_sn', $input['orderSn'])
                ->where('type', Order::ORDER_TYPE_QUESTION_REWARD)
                ->first();
            if (empty($orderChildrenInfo) ||
                $orderChildrenInfo->amount != $input['price'] ||
                $orderChildrenInfo->status != Order::ORDER_STATUS_PAID) {
                $this->outPut(ResponseCode::INVALID_PARAMETER);
            }
        }

        $order->thread_id = $this->threadId;
        $order->save();
        if ($order->type == Order::ORDER_TYPE_MERGE) {
            $orderChildrenInfo->thread_id = $this->threadId;
            $orderChildrenInfo->save();
        }

        if (empty($order['thread_id'])) {
            $this->outPut(ResponseCode::NOT_FOUND_USER);
        }

        $threadReward = new ThreadReward;
        $threadReward->thread_id = $this->threadId;
        $threadReward->post_id = $this->postId;
        $threadReward->type = $input['type'];
        $threadReward->user_id = $this->user['id'];
        $threadReward->answer_id = 0; // 目前没有指定人问答
        $threadReward->money = $input['price'];
        $threadReward->remain_money = $input['price'];
        $threadReward->is_reward = $input['isReward'];
        $threadReward->expired_at = date("Y-m-d",strtotime($input['expiredAt']));
        $threadReward->save();

        $threadReward->isSelect = false;
        $threadReward->content = $input['content'];

        return $this->jsonReturn($threadReward);
    }

    public function select()
    {
        if (isset($this->body['isSelect'])) {
            return $this->jsonReturn($this->body);
        }
        $redPacket = ThreadReward::query()->where('id',$this->body['id'])->first(['remain_money']);
        $this->body['remain_money'] = $redPacket['remain_money'];

        return $this->jsonReturn($this->body);
    }

    public function verification(){
        $input = [
            'orderSn' => $this->getParams('orderSn'),
            'price' => $this->getParams('price'),
            'type' => $this->getParams('type'),
            'expiredAt' => $this->getParams('expiredAt'),
            'content' => $this->getParams('content'),
            'isReward' => $this->getParams('isReward') ? $this->getParams('isReward') : 0,
        ];
        $rules = [
            'orderSn' => 'required|numeric',
            'price' => 'required|numeric|min:0.01',
            'type' => 'required|integer|in:0,1',
            'expiredAt' => 'required|date',
            'content' => 'max:1000',
            'isReward' => 'boolean',
        ];

        $this->dzqValidate($input, $rules);

        return $input;
    }
}
