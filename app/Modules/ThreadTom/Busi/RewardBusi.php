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
use App\Models\Thread;
use App\Modules\ThreadTom\TomBaseBusi;
use App\Models\Order;
use App\Models\OrderChildren;
use App\Models\ThreadReward;
use App\Models\ThreadTom;
use Carbon\Carbon;

class RewardBusi extends TomBaseBusi
{
    public const NEED_PAY = 1;

    public function create()
    {
        $input = $this->verification();
        if(strtotime($input['expiredAt']) < time()+24*60*60){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }

        $threadReward = ThreadReward::query()->where('thread_id', $this->threadId)->first();
        /*
        if (!empty($threadReward)) {
            $thread = Thread::query()->where('id', $this->threadId)->first(['is_draft']);
            if ($thread->is_draft == Thread::IS_NOT_DRAFT) $this->outPut(ResponseCode::INVALID_PARAMETER,'已发布的悬赏不可编辑');
        }
        */

        if (!empty($input['orderSn'])) {
            $order = Order::query()
                ->where('order_sn',$input['orderSn'])
                ->first(['id','thread_id','user_id','status','amount','expired_at','type']);

            if (empty($order) ||
                ($order->type == Order::ORDER_TYPE_QUESTION_REWARD && !empty($order['thread_id'])) ||
                $order['user_id'] != $this->user['id'] ||
                $order['status'] != Order::ORDER_STATUS_PENDING ||
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
                    $orderChildrenInfo->status != Order::ORDER_STATUS_PENDING) {
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
        }

        if (empty($threadReward)) {
            $threadReward = new ThreadReward;
        } else {
            $threadReward->updated_at = date('Y-m-d H:i:s');
        }

        $threadReward->thread_id = $this->threadId;
        $threadReward->post_id = $this->postId;
        $threadReward->type = $input['type'];
        $threadReward->user_id = $this->user['id'];
        $threadReward->answer_id = 0; // 目前没有指定人问答
        $threadReward->money = $input['price'];
        $threadReward->remain_money = $input['price'];
        $threadReward->expired_at = date('Y-m-d H:i:s', strtotime($input['expiredAt']));
        $threadReward->save();

        $threadReward->content = $input['content'];

        return $this->jsonReturn($threadReward);
    }

    public function update()
    {
        $input = $this->verification();
        //先删除原订单，这里的删除暂定为：将原订单中的 thread_id 置 0，让原订单成为僵死订单
        $old_order = Order::query()->where('thread_id', $this->threadId)->first();
        if(empty($input['orderSn']) && !empty($old_order)){
            $this->outPut(ResponseCode::INVALID_PARAMETER, '该贴已有订单，缺少 orderSn');
        }
        $threadReward = ThreadReward::query()->where(['thread_id' => $this->threadId, 'post_id' => $this->postId])->first();
        if(empty($threadReward)){
            $this->outPut(ResponseCode::INTERNAL_ERROR, '原悬赏帖数据不存在');
        }
        //如果该帖具有老订单了，并且本次请求的orderSn 与老订单的 order_sn 相同的话，则取出老 $threadReward 返回就好了
        if($old_order->order_sn && $old_order->order_sn == $input['orderSn'] ){
            return $this->jsonReturn($threadReward);
        }
        if(!empty($input['orderSn'])){
            $order = Order::query()->where('order_sn', $input['orderSn'])->first();
            if(empty($order)){
                $this->outPut(ResponseCode::INTERNAL_ERROR, 'orderSn不正确');
            }
        }
        //如果传过来的 orderSn 变更的话，就说明红包变了，那么就与原 order 脱离关系，关联新 order
        if( !empty($old_order) && !empty($input['orderSn']) && $old_order->order_sn != $input['orderSn']) {
            //规定时间内，含有红包的帖子不能频繁修改
            if ($old_order->created_at > Carbon::now()->subMinutes(self::RED_LIMIT_TIME)) {
                $this->outPut(ResponseCode::INTERNAL_ERROR, '系统处理中，请稍后再试……');
            }
            $old_order->thread_id = 0;
            $res = $old_order->save();
            if ($res === false) {
                $this->outPut(ResponseCode::INTERNAL_ERROR, '清除原订单帖子id失败');
            }
            // 将原 orderChildrenInfo 的 thread_id 置 0
            if ($old_order->type == Order::ORDER_TYPE_MERGE) {
                $orderChildrenInfo = OrderChildren::query()
                    ->where('order_sn', $input['orderSn'])
                    ->where('type', Order::ORDER_TYPE_QUESTION_REWARD)
                    ->first();
                if (empty($orderChildrenInfo) || $orderChildrenInfo->status != Order::ORDER_STATUS_PENDING) {
                    $this->outPut(ResponseCode::INVALID_PARAMETER);
                }
                $orderChildrenInfo->thread_id = 0;
                $res = $orderChildrenInfo->save();
                if ($res === false) {
                    $this->outPut(ResponseCode::INTERNAL_ERROR, '清除原子订单帖子id失败');
                }
            }
        }
        // 将原 threadReward 中 thread_id 、post_id 置 0
        $threadReward->thread_id = 0;
        $threadReward->post_id = 0;
        $res = $threadReward->save();
        if($res === false){
            $this->outPut(ResponseCode::INTERNAL_ERROR, '修改原悬赏帖数据出错');
        }
        return self::create();
    }

    public function select()
    {
        $redPacket = ThreadReward::query()->where('id',$this->body['id'])->first(['remain_money']);
        $this->body['remain_money'] = $redPacket['remain_money'];

        return $this->jsonReturn($this->camelData($this->body));
    }

    public function verification(){
        $input = [
            'orderSn' => $this->getParams('orderSn'),
            'price' => $this->getParams('price'),
            'type' => $this->getParams('type'),
            'expiredAt' => $this->getParams('expiredAt'),
            'content' => $this->getParams('content'),
            'draft' => $this->getParams('draft')
        ];
        $rules = [
            'price' => 'required|numeric|min:0.1|max:1000000',
            'type' => 'required|integer|in:0,1',
            'expiredAt' => 'required|date',
            'content' => 'max:1000',
        ];

        $input['draft'] != Thread::IS_DRAFT ? $rules['orderSn'] = 'required|numeric' : '';

        $this->dzqValidate($input, $rules);

        return $input;
    }
}
