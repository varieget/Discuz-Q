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

namespace App\Console\Commands;

use App\Models\ThreadReward;
use App\Models\Post;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use App\Models\Order;
use Carbon\Carbon;
use App\Repositories\ThreadRewardRepository;
use Discuz\Console\AbstractCommand;
use Discuz\Foundation\Application;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\ConnectionInterface;

class ThreadRewardExpireCommand extends AbstractCommand
{
    protected $signature = 'reward:expire';

    protected $description = '分配过期的剩余悬赏金额';

    protected $app;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * AvatarCleanCommand constructor.
     * @param string|null $name
     * @param Application $app
     * @param ConnectionInterface $connection
     */
    public function __construct(string $name = null, Application $app, ConnectionInterface $connection, Dispatcher $bus)
    {
        parent::__construct($name);

        $this->app = $app;
        $this->connection = $connection;
        $this->bus = $bus;
    }

    public function handle()
    {
        $today = Carbon::now()->toDateTimeString();
        $query = ThreadReward::query();
        $query->where('type', 0);
        $query->where('expired_at', '<', $today);
        $query->where('remain_money', '>', 0); // 还有剩余金额
        $threadReward = $query->get();

        $bar = $this->createProgressBar(count($threadReward));
        $bar->start();

        $threadReward->map(function ($item) use ($bar) {
            $item->remain_money = floatval(sprintf('%.2f', $item->remain_money));
            $userWallet = UserWallet::query()->lockForUpdate()->find($item->user_id);
            $threadRewardOrder = Order::query()->where(['thread_id' => $thread_id, 'status' => 1])->first();
            if($threadRewardOrder['payment_type'] == Order::PAYMENT_TYPE_WALLET && ($userWallet->freeze_amount - $item->remain_money < 0)){
                app('log')->info('过期悬赏错误：悬赏帖(ID为' . $item->thread_id . ')，作者(ID为' . $item->user_id . ')，钱包冻结金额 小于 应返回的悬赏剩余金额，悬赏剩余金额返回失败！');
            }else{
                // Start Transaction
                $this->connection->beginTransaction();
                try {
                        $postQuery = Post::query();
                        $postList = $postQuery->where(['thread_id' => $item->thread_id, 'is_approved' => 1, 'is_first' => 0, 'is_comment' => 0])->whereNull('deleted_at')->orderBy('created_at', 'asc')->get();

                        $postListArray = empty($postList) ? array() : $postList->toArray();

                        if(empty($postListArray)){

                            if($threadRewardOrder['payment_type'] == Order::PAYMENT_TYPE_WALLET){
                                $userWallet->freeze_amount = $userWallet->freeze_amount - $item->remain_money;
                            }
                            $userWallet->available_amount = $userWallet->available_amount + $item->remain_money;
                            $userWallet->save();

                            UserWalletLog::createWalletLog(
                                $item->user_id,
                                $item->remain_money,
                                -$item->remain_money,
                                UserWalletLog::TYPE_INCOME_THREAD_REWARD_RETURN,
                                trans('wallet.income_thread_reward_return_desc'),
                                null,
                                null,
                                $item->user_id,
                                0,
                                0,
                                $item->thread_id
                            );

                            // 发送悬赏问答通知
                            app(ThreadRewardRepository::class)->returnThreadRewardNotify($item->thread_id, $item->user_id, $item->remain_money, UserWalletLog::TYPE_INCOME_THREAD_REWARD_RETURN);

                            $item->remain_money = 0;
                            $item->save();

                            // 修改过期后输出
                            // $this->question('');
                            // $this->question('该帖子没有评论，钱返回给作者，结束:' . Carbon::now());
                        }else{
                            $firstPostId = $postListArray[0]['id'];
                            $likeCountPostList = $postQuery->where(['thread_id' => $item->thread_id, 'is_approved' => 1, 'is_first' => 0, 'is_comment' => 0])->where('like_count', '>', 0)->whereNull('deleted_at')->orderBy('like_count', 'desc')->get();

                            $likeCountPostListArray = empty($likeCountPostList) ? array() : $likeCountPostList->toArray();

                            if(empty($likeCountPostListArray)){
                                // nobody like the reward thread's post,every post's like is zero,so every post's author divide the money
                                $divideMoney = $item->remain_money / count($postListArray);
                                $divideMoney = floor($divideMoney * 100) / 100;

                                // 如果还有剩下的钱，分给第一位评论的人吧
                                $totalDivideMoney = $divideMoney * count($postList);
                                $firstDivideRemainMoney = $divideMoney;
                                if($item->remain_money > $totalDivideMoney){
                                    $firstDivideRemainMoney = $item->remain_money - $totalDivideMoney + $divideMoney;
                                }

                                $postList->map(function ($postItem) use ($item, $divideMoney, $firstDivideRemainMoney, $firstPostId) {
                                    if($firstPostId == $postItem->id){
                                        $total = $firstDivideRemainMoney;
                                    }else{
                                        $total = $divideMoney;
                                    }
                                    $postUserWallet = UserWallet::query()->lockForUpdate()->find($postItem->user_id);
                                    $postUserWallet->available_amount = $postUserWallet->available_amount + $total;
                                    $postUserWallet->save();

                                    UserWalletLog::createWalletLog(
                                        $postItem->user_id,
                                        $total,
                                        0,
                                        UserWalletLog::TYPE_INCOME_THREAD_REWARD_DIVIDE,
                                        trans('wallet.income_thread_reward_divide_desc'),
                                        null,
                                        null,
                                        $item->user_id,
                                        0,
                                        $postItem->id,
                                        $item->thread_id
                                    );

                                    // 发送悬赏问答通知
                                    app(ThreadRewardRepository::class)->returnThreadRewardNotify($item->thread_id, $postItem->user_id, $total, UserWalletLog::TYPE_INCOME_THREAD_REWARD_DIVIDE);
                                });
                            }else{
                                // someone like the reward thread's post,those people according to the thumb up divide the money
                                $likeCount = array_sum(array_column($likeCountPostListArray, 'like_count'));
                                $avgLikeCountMoney = $item->remain_money / $likeCount;
                                $avgLikeCountMoney = floor($avgLikeCountMoney * 100) / 100;

                                // 如果还有剩下的钱，分给第一位评论的人吧
                                $totalDivideMoney = $avgLikeCountMoney * $likeCount;
                                $firstDivideRemainMoney = 0;
                                if($item->remain_money > $totalDivideMoney){
                                    $firstDivideRemainMoney = $item->remain_money - $totalDivideMoney;
                                }

                                $likeCountPostList->map(function ($postItem) use ($item, $avgLikeCountMoney, $firstDivideRemainMoney, $firstPostId) {
                                    if($firstPostId == $postItem->id){
                                        $total = $firstDivideRemainMoney + $avgLikeCountMoney * $postItem->like_count;
                                    }else{
                                        $total = $avgLikeCountMoney * $postItem->like_count;
                                    }

                                    $postUserWallet = UserWallet::query()->lockForUpdate()->find($postItem->user_id);
                                    $postUserWallet->available_amount = $postUserWallet->available_amount + $total;
                                    $postUserWallet->save();

                                    UserWalletLog::createWalletLog(
                                        $postItem->user_id,
                                        $total,
                                        0,
                                        UserWalletLog::TYPE_INCOME_THREAD_REWARD_DISTRIBUTION,
                                        trans('wallet.income_thread_reward_distribution_desc'),
                                        null,
                                        null,
                                        $item->user_id,
                                        0,
                                        $postItem->id,
                                        $item->thread_id
                                    );

                                    // 发送悬赏问答通知
                                    app(ThreadRewardRepository::class)->returnThreadRewardNotify($item->thread_id, $postItem->user_id, $total, UserWalletLog::TYPE_INCOME_THREAD_REWARD_DISTRIBUTION);
                                });
                            }

                            // 减少作者的冻结金额
                            if($threadRewardOrder['payment_type'] == Order::PAYMENT_TYPE_WALLET){
                                $userWallet->freeze_amount = $userWallet->freeze_amount - $item->remain_money;
                            }
                            $userWallet->save();

                            // 清零作者悬赏帖的剩余金额
                            $item->remain_money = 0;
                            $item->save();
                        }
                        $this->connection->commit();
                } catch (Exception $e) {
                    $this->connection->rollback();
                }
            }
            $bar->advance();
        });

        $bar->finish();
    }
}
