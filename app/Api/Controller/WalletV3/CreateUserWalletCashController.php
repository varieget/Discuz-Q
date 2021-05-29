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

namespace App\Api\Controller\WalletV3;


use App\Common\ResponseCode;
use App\Models\UserWallet;
use App\Models\UserWalletCash;
use App\Models\UserWalletLog;
use App\Repositories\UserRepository;
use App\Settings\SettingsRepository;
use Carbon\Carbon;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateUserWalletCashController extends DzqController
{
    use AssertPermissionTrait;

    private $settings;


    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $actor = $this->user;
        if ($actor->isGuest()) {
            throw new PermissionDeniedException('没有权限');
        }
        if (!$userRepo->canCreateCash($actor)) {
            throw new PermissionDeniedException('没有提现权限');
        }
        return true;
    }

    public function main()
    {
        $cashApplyAmount = $this->inPut('cashApplyAmount');
        if(empty($cashApplyAmount)){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }
        $cash_setting = $this->settings->tag('cash');
        $cash_interval_time = (int)Arr::get($cash_setting, 'cash_interval_time', 0);//提现间隔
        $cash_rate = (float)Arr::get($cash_setting, 'cash_rate', 0);//提现手续费
        $cash_sum_limit = (float)Arr::get($cash_setting, 'cash_sum_limit', 5000);//每日总提现额
        $cash_max_sum = (float)Arr::get($cash_setting, 'cash_max_sum', 5000);//每次最大金额
        $cash_min_sum = (float)Arr::get($cash_setting, 'cash_min_sum', 0);//每次最小金额
        $this->dzqValidate([
            'cashApplyAmount'   =>  $cashApplyAmount
        ],
        [
            'cashApplyAmount'   =>  "required|numeric|min:{$cash_min_sum}|max:{$cash_max_sum}"
        ]);

        if ($cash_interval_time != 0) {
            $time_before = Carbon::now()->addDays(-$cash_interval_time);
            //提现间隔时间
            $cash_record = UserWalletCash::where('created_at', '>=', $time_before)->where('user_id', $this->user->id)->first();
            if (!empty($cash_record)) {
                return $this->outPut(ResponseCode::NET_ERROR, '提现处于限制间隔天数内');
            }
        }
        //今日已申提现总额
        $totday_cash_amount = UserWalletCash::where('user_id', $this->user->id)
            ->where('created_at', '>=', Carbon::today())
            ->where('refunds_status', UserWalletCash::REFUNDS_STATUS_NO)
            ->sum('cash_apply_amount');
        if(bccomp($cash_sum_limit, $totday_cash_amount, 2) == -1){
            return $this->outPut(ResponseCode::NET_ERROR, '超出每日提现金额限制');
        }
        //计算手续费
        $tax_ratio  = $cash_rate; //手续费率
        $tax_amount = $cashApplyAmount * $tax_ratio; //手续费
        $tax_amount = sprintf('%.2f', ceil($tax_amount * 100) / 100); //格式化手续费
        $db = app('db');
        //开始事务
        $db->beginTransaction();
        try {
            //获取用户钱包
            $user_wallet = $this->user->userWallet()->lockForUpdate()->first();
            //检查钱包是否允许提现,1:钱包已冻结
            if ($user_wallet->wallet_status == UserWallet::WALLET_STATUS_FROZEN) {
                $db->rollback();
                return $this->outPut(ResponseCode::NET_ERROR, '钱包已冻结提现');
            }
            //检查金额是否足够
            if ($user_wallet->available_amount < $cashApplyAmount) {
                $db->rollback();
                return $this->outPut(ResponseCode::NET_ERROR, '钱包可用金额不足');
            }
            $cash_sn  = $this->getCashSn();
            $cash_actual_amount = sprintf('%.2f', ($cashApplyAmount - $tax_amount));
            //创建提现记录
            $cash = UserWalletCash::createCash(
                $this->user->id,
                $cash_sn,
                $tax_amount,
                $cash_actual_amount,
                $cashApplyAmount,
                '',
                0,              //创建申请提现时，默认为0，具体为什么值，待后台审核通过之后扣款才修改该值
                ''
            );
            //冻结钱包金额
            $user_wallet->available_amount = $user_wallet->available_amount - $cashApplyAmount;
            $user_wallet->freeze_amount    = $user_wallet->freeze_amount + $cashApplyAmount;
            $res = $user_wallet->save();
            if(!$res){
                $db->rollBack();
                return $this->outPut(ResponseCode::NET_ERROR, '提现申请失败');
            }
            //添加钱包明细,
            $res = UserWalletLog::createWalletLog(
                $this->user->id,
                -$cashApplyAmount,
                $cashApplyAmount,
                UserWalletLog::TYPE_CASH_FREEZE,
                app('translator')->get('wallet.cash_freeze_desc'),
                $cash->id
            );
            if(!$res){
                $db->rollBack();
                return $this->outPut(ResponseCode::NET_ERROR, '提现申请失败');
            }
            $db->commit();
            return $this->outPut(ResponseCode::SUCCESS,'申请提现成功');
        }catch (\Exception $e){
            $db->rollBack();
            return $this->outPut(ResponseCode::NET_ERROR, '提现申请失败');
        }
    }

    /**
     * 生成提现编号
     * @return string  18位字符串
     */
    public function getCashSn()
    {
        return date('Ymd')
            . str_pad(strval(mt_rand(1, 99)), 2, '0', STR_PAD_LEFT)
            . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }


}
