<?php

namespace App\Api\Controller\OrderV3;

use App\Common\ResponseCode;
use App\Models\Order;
use App\Models\User;
use App\Models\Thread;
use App\Repositories\UserRepository;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;

class UsersOrderLogsListController extends DzqController
{
    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if (!$this->user->isAdmin()) {
            throw new PermissionDeniedException('没有权限');
        }
        return true;
    }

    public function main()
    {
        $currentPage = $this->inPut('page');
        $perPage = $this->inPut('perPage');
        $filter = (array)$this->inPut('filter');

        $query = Order::query();
        $query->select('orders.id as orderId', 'orders.user_id', 'orders.payee_id', 'orders.thread_id','users.nickname', 'orders.order_sn', 'orders.type', 'orders.amount', 'orders.status', 'orders.created_at',);
        $query->join('users', 'orders.user_id', '=', 'users.id');
        if (isset($filter['orderSn']) && !empty($filter['orderSn'])) {
            $query->where('orders.order_sn', $filter['orderSn']);
        }

        if (isset($filter['status']) && !empty($filter['status'])) {
            $query->where('orders.status', $filter['status']);
        }

        if (isset($filter['startTime']) && !empty($filter['startTime'])) {
            $query->where('orders.created_at', '>=', $filter['startTime']);
        }

        if (isset($filter['endTime']) && !empty($filter['endTime'])) {
            $query->where('orders.created_at', '<=', $filter['endTime']);
        }

        // 发起方
        if (isset($filter['nickname']) && !empty($filter['nickname'])) {
            $query->where('users.nickname', 'like', '%' . $filter['nickname'] . '%');
        }

        // 收入方
        if (isset($filter['payeeNickname']) && !empty($filter['payeeNickname'])) {
            $query->where('users.nickname', 'like', '%' . $filter['payeeNickname'] . '%');
        }

        // 商品
        if (isset($filter['product']) && !empty($filter['product'])) {
            $threadIds = Thread::query()
                ->join('posts', 'threads.id', '=', 'posts.thread_id')
                ->where('posts.is_first', 1)
                ->where('threads.title', 'like', '%' . $filter['product'] . '%')
                ->orWhere('posts.content', 'like', '%' . $filter['product'] . '%')
                ->pluck('threads.id')->toArray();
            $query->whereIn('orders.thread_id', $threadIds);
        }

        $query->orderByDesc('orders.created_at');
        $usersOrderLogs = $this->pagination($currentPage, $perPage, $query);

        $orders = $usersOrderLogs['pageData'];
        $orderThreadIds = array_column($orders, 'thread_id');
        $payeeUserIds = array_column($orders, 'payee_id');
        $payeeUserDatas = User::instance()->getUsers($payeeUserIds);
        $payeeUserDatas = array_column($payeeUserDatas, null, 'id');
        foreach ($orderThreadIds as $key => $value) {
            if (empty($value)) {
                unset($orderThreadIds[$key]);
            }
        }
        $orderThreadIds = array_merge($orderThreadIds);
        $threadData = $this->getThreadsBuilder($orderThreadIds);
        $threadData = array_column($threadData, null, 'threadId');
        foreach ($orders as $key => $value) {
            $orders[$key]['payeeNickname'] = $payeeUserDatas[$value['payee_id']]['nickname'] ?? '';
            $orders[$key]['thread'] = $threadData[$value['thread_id']] ?? [];
        }

        $usersOrderLogs['pageData'] = $this->camelData($orders) ?? [];
        return $this->outPut(ResponseCode::SUCCESS, '', $usersOrderLogs);
    }

    private function getThreadsBuilder($orderThreadIds)
    {
        return Thread::query()
            ->select('threads.id as threadId', 'threads.user_id', 'threads.title', 'posts.content')
            ->join('posts', 'threads.id', '=', 'posts.thread_id')
            ->where('posts.is_first', 1)
            ->whereIn('threads.id', $orderThreadIds)
            ->get()->toArray();
    }
}
