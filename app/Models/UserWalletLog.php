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

namespace App\Models;

use App\Events\WalletLog\Created;
use Carbon\Carbon;
use Discuz\Database\ScopeVisibilityTrait;
use Discuz\Foundation\EventGeneratorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property int $source_user_id
 * @property float $change_available_amount
 * @property float $change_freeze_amount
 * @property int $change_type
 * @property string $change_desc
 * @property int $order_id
 * @property int $user_wallet_cash_id
 * @property int $question_id
 * @property int $thread_id
 * @property int $post_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Order $order
 * @package App\Models
 */
class UserWalletLog extends Model
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;

    /**
     * {@inheritdoc}
     */
    protected $table = 'user_wallet_logs';

    /**
     * 钱包明细类型
     */
    const TYPE_QUESTION_FREEZE = 8; // 问答冻结

    const TYPE_QUESTION_RETURN_THAW = 9; // 问答返还解冻

    const TYPE_CASH_FREEZE = 10; //提现冻结

    const TYPE_CASH_SUCCESS = 11; //提现成功

    const TYPE_CASH_THAW = 12; //提现解冻

    const TYPE_INCOME_REGISTER = 30; //注册收入

    const TYPE_INCOME_SCALE_REGISTER = 34; //注册分成收入

    const TYPE_INCOME_REWARD = 31; //打赏收入

    const TYPE_INCOME_ARTIFICIAL = 32; //人工收入

    const TYPE_INCOME_SCALE_REWARD = 33; //分成打赏收入

    const TYPE_INCOME_QUESTION_REWARD = 35; // 问答答题收入

    const TYPE_INCOME_ONLOOKER_REWARD = 36; // 问答围观收入

    const TYPE_EXPEND_ARTIFICIAL = 50; //人工支出

    const TYPE_EXPEND_GROUP = 51; //加入用户组支出

    const TYPE_EXPEND_ATTACHMENT = 52; //付费附件支出

    // ----- 分割线 -----

    const TYPE_EXPEND_REWARD = 41; //打赏支出

    const TYPE_INCOME_THREAD = 60; //付费主题收入

    const TYPE_EXPEND_THREAD = 61; //付费主题支出

    const TYPE_INCOME_SCALE_THREAD = 62; //分成付费主题收入

    const TYPE_INCOME_ATTACHMENT = 63; //付费附件收入

    const TYPE_INCOME_SCALE_ATTACHMENT = 64; //付费附件分成收入

    const TYPE_EXPEND_RENEW = 71; //站点续费支出

    const TYPE_EXPEND_QUESTION = 81; // 问答提问支出

    const TYPE_EXPEND_ONLOOKER = 82; // 问答围观支出

    // ----- 分割线(以下为红版新增枚举值100-130) -----

    const TYPE_EXPEND_TEXT = 100; // 文字帖红包支出

    const TYPE_TEXT_FREEZE = 101; // 文字帖红包冻结

    const TYPE_INCOME_TEXT = 102; // 文字帖红包收入

    const TYPE_TEXT_RETURN_THAW = 103; // 文字帖冻结返还

    const TYPE_TEXT_ABNORMAL_REFUND = 104; // 文字帖订单异常返现

    const TYPE_EXPEND_LONG = 110; // 长文帖红包支出

    const TYPE_LONG_FREEZE = 111; // 长文帖红包冻结

    const TYPE_INCOME_LONG = 112; // 长文帖红包收入

    const TYPE_LONG_RETURN_THAW = 113; // 长文帖冻结返还

    const TYPE_LONG_ABNORMAL_REFUND = 114; // 长文帖订单异常返现

    const TYPE_INCOME_THREAD_REWARD = 120; // 悬赏问答收入

    const TYPE_INCOME_THREAD_REWARD_RETURN = 121; // 悬赏帖过期-悬赏帖剩余悬赏金额返回

    const TYPE_INCOME_THREAD_REWARD_DIVIDE = 122; // 悬赏帖过期-悬赏帖剩余悬赏金额平分

    const TYPE_INCOME_THREAD_REWARD_DISTRIBUTION = 123; // 悬赏帖过期-悬赏帖剩余悬赏金额按点赞数分配

    const TYPE_QUESTION_ABNORMAL_REFUND = 124; // 问答帖订单异常返现

    const TYPE_ABNORMAL_ORDER_REFUND = 130; // 异常订单退款

    /* -------分割线(以下为V3新增枚举值150-170) ----- */
    const TYPE_REDPACKET_FREEZE = 150; // 红包冻结

    const TYPE_REDPACKET_INCOME = 151; // 红包收入

    const TYPE_REDPACKET_REFUND = 152; // 红包退款

    const TYPE_REDPACKET_EXPEND = 153; // 红包支出

    const TYPE_REDPACKET_ORDER_ABNORMAL_REFUND = 154; // 红包订单异常退款

    const TYPE_QUESTION_REWARD_FREEZE = 160; // 悬赏问答冻结

    const TYPE_QUESTION_REWARD_INCOME = 161; // 悬赏问答收入

    const TYPE_QUESTION_REWARD_REFUND = 162; // 悬赏问答退款

    const TYPE_QUESTION_ORDER_ABNORMAL_REFUND = 163; // 悬赏订单异常退款

    const TYPE_QUESTION_REWARD_EXPEND = 164; // 悬赏采纳支出

    const TYPE_QUESTION_REWARD_FREEZE_RETURN = 165; // 悬赏冻结返回

    const TYPE_MERGE_FREEZE = 170; // 合并订单冻结

    const TYPE_MERGE_REFUND = 171; // 合并订单退款

    const TYPE_MERGE_ORDER_ABNORMAL_REFUND = 172; // 合并订单异常退款

    /**
     * 创建钱包动账记录
     *
     * @param int $user_id
     * @param float $change_available_amount
     * @param float $change_freeze_amount
     * @param int $change_type
     * @param string $change_desc
     * @param int|null $user_wallet_cash_id
     * @param int|null $order_id
     * @param int $source_user_id
     * @param int $question_id
     * @param int $thread_id
     * @param int $post_id
     * @return UserWalletLog
     */
    public static function createWalletLog(
        $user_id,
        $change_available_amount,
        $change_freeze_amount,
        $change_type,
        $change_desc,
        $user_wallet_cash_id = null,
        $order_id = null,
        $source_user_id = 0,
        $question_id = 0,
        $post_id = 0,
        $thread_id = 0
    ) {
        $walletLog = new static;
        $walletLog->user_id = $user_id;
        $walletLog->change_available_amount = $change_available_amount;
        $walletLog->change_freeze_amount = $change_freeze_amount;
        $walletLog->change_type = $change_type;
        $walletLog->change_desc = $change_desc;
        $walletLog->user_wallet_cash_id = $user_wallet_cash_id;
        $walletLog->order_id = $order_id;
        $walletLog->source_user_id = $source_user_id;
        $walletLog->question_id = $question_id;
        $walletLog->post_id = $post_id;
        $walletLog->thread_id = $thread_id;

        $walletLog->save();

        $walletLog->raise(new Created($walletLog));

        return $walletLog;
    }

    /**
     * Define the relationship with the log's wallet.
     *
     * @return belongsTo
     */
    public function userWallet()
    {
        return $this->belongsTo(UserWallet::class, 'user_id', 'user_id');
    }

    /**
     * Define the relationship with the wallet log's owner.
     *
     * @return belongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define the relationship with the cash log.
     *
     * @return belongsTo
     */
    public function userWalletCash()
    {
        return $this->belongsTo(UserwalletCash::class);
    }

    /**
     * Define the relationship with the log order.
     *
     * @return belongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function sourceUser()
    {
        return $this->belongsTo(User::class, 'source_user_id', 'id');
    }
}
