<?php

namespace App\Api\Controller\OrderV3;

use App\Common\ResponseCode;
use App\Models\Order;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\NotAuthenticatedException;
use Discuz\Base\DzqController;

class ResourceOrderController extends DzqController
{
    use AssertPermissionTrait;

    public function main()
    {
        $user = $this->user;
        try {
            $this->assertRegistered($user);
        } catch (NotAuthenticatedException $e) {
            $this->outPut(ResponseCode::JUMP_TO_LOGIN);
        }
        $order = Order::query()
            ->where([
                        'user_id' => $user->id,
                        'order_sn' => $this->inPut('orderSn'),
                    ])
            ->first();
        if(empty($order)){
            $this->outPut(ResponseCode::INVALID_PARAMETER, '');
        }
        $order = [
            'id' => $order->id,
            'orderSn' => (string) $order->order_sn,
            'amount' => $order->amount,
            'status' => $order->status,
            'type' => $order->type,
            'threadId' => $order->thread_id,
            'groupId' => $order->group_id,
            'updatedAt' => optional($order->updated_at)->format('Y-m-d H:i:s'),
            'createdAt' => optional($order->created_at)->format('Y-m-d H:i:s'),
        ];
        $this->outPut(ResponseCode::SUCCESS, '', $order);
    }
}
