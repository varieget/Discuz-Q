<?php

namespace App\Commands\Order;

use App\Commands\Wallet\ChangeUserWallet;
use App\Models\Order;
use App\Models\OrderChildren;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Psr\Log\LoggerInterface;

class RefundErrorThreadOrder
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string
     */
    protected $orderSn;

    /**
     * @var LoggerInterface
     */
    protected $log;

    public function __construct(ConnectionInterface $connection, Dispatcher $bus, LoggerInterface $log)
    {
        $this->connection = $connection;
        $this->bus = $bus;
        $this->log = $log;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function setOrderSn(string $orderSn)
    {
        $this->orderSn = $orderSn;
        return $this;
    }

    public function handle()
    {
        $orderTypes = [
            Order::ORDER_TYPE_QUESTION, Order::ORDER_TYPE_REDPACKET,
            Order::ORDER_TYPE_QUESTION_REWARD, Order::ORDER_TYPE_MERGE,
            Order::ORDER_TYPE_TEXT, Order::ORDER_TYPE_LONG,
        ];

        $query = Order::query();
        if ($this->orderSn) {
            $query->where('order_sn', $this->orderSn);
        } elseif ($this->user) {
            $query->where('user_id', $this->user->id);
        }

        $query->where('status', Order::ORDER_STATUS_PAID)
            ->where(function (Builder $query) {
                $query->whereNull('thread_id')->orWhere('thread_id', 0);
            })
            ->where('amount', '>', 0);

        // 如果没有指定订单号，则限制时间范围
        if (!$this->orderSn) {
            $query->whereBetween('created_at', [
                Carbon::parse()->subDay()->format('Y-m-d 00:00:00'),
                Carbon::parse()->subMinute(),
            ]);
        }

        $orders = $query->whereIn('type', $orderTypes)->get();

        $orders->each(function (Order $order) {
            /** @var UserWallet $userWallet */
            $userWallet = UserWallet::query()->where('user_id', $order->user_id)->first();
            if (empty($userWallet)) {
                $this->log->info('未获取到订单创建者的钱包信息，无法处理订单金额！;订单号为：' . $order->order_sn . '，订单创建者ID为：' . $order->user_id);
                $order->status = Order::ORDER_STATUS_UNTREATED;
                $order->save();
                return;
            }

            if ($order->payment_type == Order::PAYMENT_TYPE_WALLET && $userWallet->freeze_amount < $order->amount) {
                $this->log->info('用户冻结金额小于订单金额，无法退还订单金额！订单号为：' . $order->order_sn . ';订单创建者ID为：' . $order->user_id . ';用户冻结金额:' . $userWallet->freeze_amount . ';应退还金额:' . $order->amount);
                $order->status = Order::ORDER_STATUS_UNTREATED;
                $order->save();
                return;
            }

            $changeMap = [
                Order::ORDER_TYPE_TEXT => [UserWalletLog::TYPE_TEXT_ABNORMAL_REFUND, trans('wallet.abnormal_return_text')],
                Order::ORDER_TYPE_LONG => [UserWalletLog::TYPE_LONG_ABNORMAL_REFUND, trans('wallet.abnormal_return_long')],
                Order::ORDER_TYPE_QUESTION => [UserWalletLog::TYPE_QUESTION_ABNORMAL_REFUND, trans('wallet.abnormal_return_question')],
                Order::ORDER_TYPE_REDPACKET => [UserWalletLog::TYPE_REDPACKET_ORDER_ABNORMAL_REFUND, trans('wallet.redpacket_order_abnormal_refund')],
                Order::ORDER_TYPE_QUESTION_REWARD => [UserWalletLog::TYPE_QUESTION_ORDER_ABNORMAL_REFUND, trans('wallet.question_order_abnormal_refund')],
                Order::ORDER_TYPE_MERGE => [UserWalletLog::TYPE_MERGE_ORDER_ABNORMAL_REFUND, trans('wallet.merge_order_abnormal_refund')],
            ];
            [$changeType, $changeDesc] = $changeMap[$order->type];
            $data = [
                'order_id' => $order->id,
                'thread_id' => $order->thread_id ?: 0,
                'post_id' => $order->post_id ?: 0,
                'change_type' => $changeType,
                'change_desc' => $changeDesc,
            ];

            // Start Transaction
            $this->connection->beginTransaction();
            try {
                if ($order->payment_type == Order::PAYMENT_TYPE_WALLET) {
                    $walletOperate = UserWallet::OPERATE_UNFREEZE;
                    // 钱包支付 减少冻结金额，增加可用金额
                } elseif (in_array($order->payment_type, [
                    Order::PAYMENT_TYPE_WECHAT_NATIVE,
                    Order::PAYMENT_TYPE_WECHAT_WAP,
                    Order::PAYMENT_TYPE_WECHAT_JS,
                    Order::PAYMENT_TYPE_WECHAT_MINI,
                ])) {
                    $this->bus->dispatch(new ChangeUserWallet($order->user,
                        UserWallet::OPERATE_INCREASE,
                        $order->amount,
                        $data
                    ));
                } else {
                    $this->log->info('订单金额退还失败, 订单号 ' . $order->order_sn . '的支付类型: ' . $order->payment_type . ', 不在处理范围内');
                    $order->status = Order::ORDER_STATUS_UNTREATED;
                    $order->save();
                    $this->connection->commit();
                    return;
                }

                $this->bus->dispatch(new ChangeUserWallet($order->user,
                    UserWallet::OPERATE_UNFREEZE,
                    $order->amount,
                    $data
                ));

                if ($order->type == Order::ORDER_TYPE_MERGE) {
                    $orderChildrenInfo = OrderChildren::query()
                        ->where('status', Order::ORDER_STATUS_PAID)
                        ->where('order_sn', $order->order_sn)
                        ->whereNull('thread_id')
                        ->orWhere('thread_id', 0)
                        ->get();
                    $orderChildrenInfo->map(function ($child) {
                        $child->refund = $child->amount;
                        $child->status = Order::ORDER_STATUS_RETURN;
                        $child->return_at = Carbon::now();
                        $child->save();
                    });
                }
                $order->status = Order::ORDER_STATUS_RETURN;
                $order->refund = $order->amount;
                $order->return_at = Carbon::now();
                $order->save();
                $this->connection->commit();
            } catch (Exception $e) {
                $this->log->info('订单金额退还失败, 订单号 ' . $order->order_sn . '异常抛出: ' . $e->getMessage());
                $this->connection->rollback();
            }
        });
    }
}
