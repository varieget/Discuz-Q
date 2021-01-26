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
        $query->where('expired_at', '<=', $today);
        $query->where('remain_money', '>', 0); // 还有剩余金额
        $threadReward = $query->get();

        $bar = $this->createProgressBar(count($threadReward));
        $bar->start();

        $threadReward->map(function ($item) use ($bar) {
            // Start Transaction
            $this->connection->beginTransaction();
            try {
                /** @var threadReward $item */
                $item->remain_money = floatval(sprintf('%.2f', $item->remain_money));

                $postQuery = Post::query();
				$postList = $postQuery->where(['thread_id' => $item->thread_id, 'is_approved' => 1, 'is_first' => 0, 'is_comment' => 0])->whereNull('deleted_at')->orderBy('created_at', 'asc')->get();

				$postListArray = empty($postList) ? array() : $postList->toArray();

				if(empty($postListArray)){
					// the reward thread doesn't have post,return reward money
					$userWallet = UserWallet::query()->lockForUpdate()->find($item->user_id);
		    		$userWallet->freeze_amount = $userWallet->freeze_amount - $item->remain_money;
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
					$likeCountPostList = $postQuery->where(['thread_id' => $item->thread_id, 'is_approved' => 1, 'is_first' => 0, 'is_comment' => 0])->where('like_count', '>', 0)->whereNull('deleted_at')->orderBy('created_at', 'asc')->get();

					$likeCountPostListArray = empty($likeCountPostList) ? array() : $likeCountPostList->toArray();

					if(empty($likeCountPostListArray)){
						// nobody like the reward thread's post,every post's like is zero,so every post's author divide the money
						$divideMoney = $item->remain_money / count($postListArray);
						$divideMoney = sprintf("%.2f", substr(sprintf("%.3f", $divideMoney), 0, -2));
						$divideMoney = floatval($divideMoney);

						$postList->map(function ($postItem) use ($item, $divideMoney) {
							$postUserWallet = UserWallet::query()->lockForUpdate()->find($postItem->user_id);
				    		$postUserWallet->available_amount = $postUserWallet->available_amount + $divideMoney;
				    		$postUserWallet->save();

				    		UserWalletLog::createWalletLog(
				                $postItem->user_id,
				                $divideMoney,
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
	                		app(ThreadRewardRepository::class)->returnThreadRewardNotify($item->thread_id, $postItem->user_id, $divideMoney, UserWalletLog::TYPE_INCOME_THREAD_REWARD_DIVIDE);
						});

						// 如果还有剩下的钱，分给第一位评论的人吧
						$totalDivideMoney = $divideMoney * count($postList);
						if($item->remain_money > $totalDivideMoney){
							$divideRemainMoney = $item->remain_money - $totalDivideMoney;
							$firstPost = Post::query()->findOrFail($postListArray[0]['id']);
							$firstPostUserWallet = UserWallet::query()->lockForUpdate()->find($firstPost->user_id);
				    		$firstPostUserWallet->available_amount = $firstPostUserWallet->available_amount + $divideRemainMoney;
				    		$firstPostUserWallet->save();

				    		UserWalletLog::createWalletLog(
				                $firstPost->user_id,
				                $divideRemainMoney,
				                0,
				                UserWalletLog::TYPE_INCOME_THREAD_REWARD_DIVIDE,
				                trans('wallet.income_thread_reward_divide_desc'),
				                null,
				                null,
				                $item->user_id,
				                0,
				                $firstPost->id,
				                $item->thread_id
				            );

			                // 发送悬赏问答通知
	                		app(ThreadRewardRepository::class)->returnThreadRewardNotify($item->thread_id, $firstPost->user_id, $divideRemainMoney, UserWalletLog::TYPE_INCOME_THREAD_REWARD_DIVIDE);
						}
					}else{
						// someone like the reward thread's post,those people according to the thumb up divide the money
						$likeCount = array_sum(array_column($likeCountPostListArray, 'like_count'));
						$avgLikeCountMoney = $item->remain_money / $likeCount;
						$avgLikeCountMoney = sprintf("%.2f", substr(sprintf("%.3f", $avgLikeCountMoney), 0, -2));
						$avgLikeCountMoney = floatval($avgLikeCountMoney);

						$likeCountPostList->map(function ($postItem) use ($item, $avgLikeCountMoney) {
			                $rewardMoney = $avgLikeCountMoney * $postItem->like_count;
			                $postUserWallet = UserWallet::query()->lockForUpdate()->find($postItem->user_id);
				    		$postUserWallet->available_amount = $postUserWallet->available_amount + $rewardMoney;
				    		$postUserWallet->save();

				    		UserWalletLog::createWalletLog(
				                $postItem->user_id,
				                $rewardMoney,
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
	                		app(ThreadRewardRepository::class)->returnThreadRewardNotify($item->thread_id, $postItem->user_id, $rewardMoney, UserWalletLog::TYPE_INCOME_THREAD_REWARD_DISTRIBUTION);
						});

						// 如果还有剩下的钱，分给第一位评论的人吧
						$totalRemainMoney = $avgLikeCountMoney * $likeCount;
						if($item->remain_money > $totalRemainMoney){
							$divideRemainMoney = $item->remain_money - $totalRemainMoney;
			                $firstPost = Post::query()->findOrFail($likeCountPostListArray[0]['id']);
							$firstPostUserWallet = UserWallet::query()->lockForUpdate()->find($firstPost->user_id);
				    		$firstPostUserWallet->available_amount = $firstPostUserWallet->available_amount + $divideRemainMoney;
				    		$firstPostUserWallet->save();

				    		UserWalletLog::createWalletLog(
				                $firstPost->user_id,
				                $divideRemainMoney,
				                0,
				                UserWalletLog::TYPE_INCOME_THREAD_REWARD_DISTRIBUTION,
				                trans('wallet.income_thread_reward_distribution_desc'),
				                null,
				                null,
				                $item->user_id,
				                0,
				                $firstPost->id,
				                $item->thread_id
				            );

			                // 发送悬赏问答通知
	                		app(ThreadRewardRepository::class)->returnThreadRewardNotify($item->thread_id, $firstPost->user_id, $divideRemainMoney, UserWalletLog::TYPE_INCOME_THREAD_REWARD_DISTRIBUTION);
						}
					}

					// 减少作者的冻结金额
	                $userWallet = UserWallet::query()->lockForUpdate()->find($item->user_id);
		    		$userWallet->freeze_amount = $userWallet->freeze_amount - $item->remain_money;
		    		$userWallet->save();

					// 清零作者悬赏帖的剩余金额
	                $item->remain_money = 0;
	                $item->save();
				}
                $this->connection->commit();
            } catch (Exception $e) {
                $this->connection->rollback();
            }

            $bar->advance();
        });

        $bar->finish();
    }
}
