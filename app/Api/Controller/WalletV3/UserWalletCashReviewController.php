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

namespace App\Api\Controller\WalletV3;

use App\Common\ResponseCode;
use App\Events\Wallet\Cash;
use App\Exceptions\WalletException;
use App\Models\UserWallet;
use App\Models\UserWalletCash;
use App\Models\UserWalletLog;
use App\Repositories\UserRepository;
use App\Settings\SettingsRepository;
use App\Trade\Config\GatewayConfig;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserWalletCashReviewController extends DzqController
{
    private $settings;
    private $data;
    /**
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    public $events;
    public $db;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(SettingsRepository $settings, Dispatcher $events)
    {
        $this->settings = $settings;
        $this->events = $events;
        $this->db = app('db');
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if (!$this->user->isAdmin()) {
            throw new PermissionDeniedException('没有权限');
        }
        return true;
    }

    /**
     * {@inheritdoc}
     * @throws WalletException
     */
    public function main()
    {
        $this->ip_address = ip($this->request->getServerParams());
        $this->data = [
            'ids'           => (array) $this->inPut('ids'),
            'cashStatus'    => (int) $this->inPut('cashStatus'),
            'remark'        => $this->inPut('remark'),
        ];

        $this->dzqValidate($this->data,[
            'ids'           => 'required|array',
            'cashStatus'    => 'required|int',
            'remark'        => 'sometimes|string|max:255',
        ]);

        //只允许修改为审核通过或审核不通过
        if (!in_array($this->data['cashStatus'], [UserWalletCash::STATUS_REVIEWED, UserWalletCash::STATUS_REVIEW_FAILED])) {
            return $this->outPut(ResponseCode::NET_ERROR, '非法操作');
        }
        $ip = ip($this->request->getServerParams());
        $cash_status    = $this->data['cashStatus'];
        $status_result  = []; //结果数组
        //是否开启企业打款
        $wxpay_mchpay_close = (bool)$this->settings->get('wxpay_mchpay_close', 'wxpay');
        $db = $this->db;
        $collection = collect($this->data['ids'])
            ->unique()
            ->map(function ($id) use ($cash_status, &$status_result, $wxpay_mchpay_close, $ip, $db) {
                $db->beginTransaction();
                //取出待审核数据
                $cash_record = UserWalletCash::find($id);
                //只允许修改未审核的数据。
                if (empty($cash_record) || $cash_record->cash_status != UserWalletCash::STATUS_REVIEW) {
                    $db->rollBack();
                    return $status_result[$id] = 'failure';
                }
                $cash_record->cash_status = $cash_status;
                if ($cash_status == UserWalletCash::STATUS_REVIEW_FAILED) {
                    $cash_apply_amount = $cash_record->cash_apply_amount;//提现申请金额
                    //审核不通过解冻金额
                    $user_id = $cash_record->user_id;
                    //开始事务
                    try {
                        //获取用户钱包
                        $user_wallet = UserWallet::lockForUpdate()->find($user_id);
                        //返回冻结金额至用户钱包
                        $user_wallet->freeze_amount    = $user_wallet->freeze_amount - $cash_apply_amount;
                        $user_wallet->available_amount = $user_wallet->available_amount + $cash_apply_amount;
                        $res = $user_wallet->save();
                        if($res === false){
                            $db->rollBack();
                            return $status_result[$id] = 'failure';
                        }
                        //冻结变动金额，为负数数
                        $change_freeze_amount = -$cash_apply_amount;
                        //可用金额增加
                        $change_available_amount = $cash_apply_amount;
                        //添加钱包明细
                        $res = UserWalletLog::createWalletLog(
                            $user_id,
                            $change_available_amount,
                            $change_freeze_amount,
                            UserWalletLog::TYPE_CASH_THAW,
                            app('translator')->get('wallet.cash_review_failure'),
                            $cash_record->id
                        );
                        if(!$res){
                            $db->rollBack();
                            return $status_result[$id] = 'failure';
                        }
                        $cash_record->remark = Arr::get($this->data, 'remark', '');
                        $cash_record->refunds_status = UserWalletCash::REFUNDS_STATUS_YES;
                        $res = $cash_record->save();
                        if($res === false){
                            $db->rollBack();
                            return $status_result[$id] = 'failure';
                        }
                        return $status_result[$id] = 'success';
                    } catch (\Exception $e) {
                        //回滚事务
                        $db->rollback();
                        return $status_result[$id] = 'failure';
                    }
                }

                // 审核通过，判断后台是否开了微信自动打款，如果开了就走自动打款，否则直接已打款，站长线下打款
                if($wxpay_mchpay_close){        //开通了企业打款
                    try {
                        //检查证书
                        if (!file_exists(storage_path().'/cert/apiclient_cert.pem') || !file_exists(storage_path().'/cert/apiclient_key.pem')) {
                            $db->rollBack();
                            return $status_result[$id] = 'pem_notexist';
                        }
                        $cash_record->cash_type = UserWalletCash::TRANSFER_TYPE_MCH;
                        $res = $cash_record->save();
                        if($res === false){
                            $db->rollBack();
                            return $status_result[$id] = 'failure';
                        }
                        //触发提现钩子事件
                        $this->events->dispatch(
                            new Cash($cash_record, $ip, GatewayConfig::WECAHT_TRANSFER)
                        );
                        $db->commit();
                        return $status_result[$id] = 'success';
                    }catch (\Exception $e){
                        $db->rollBack();
                        return $status_result[$id] = 'failure';
                    }
                }else{          //没有开通企业打款，直接扣款
                    try {
                        $cash_record->cash_type = UserWalletCash::TRANSFER_TYPE_MANUAL;
                        $cash_record->remark = Arr::get($this->data, 'remark', '');
                        $cash_record->cash_status = UserWalletCash::STATUS_PAID;//已打款
                        $res = $cash_record->save();
                        if($res === false){
                            $db->rollBack();
                            return $status_result[$id] = 'failure';
                        }
                        //获取用户钱包
                        $user_wallet = UserWallet::lockForUpdate()->find($cash_record->user_id);
                        //去除冻结金额
                        $user_wallet->freeze_amount = $user_wallet->freeze_amount - $cash_record->cash_apply_amount;
                        $res = $user_wallet->save();
                        if($res === false){
                            $db->rollBack();
                            return $status_result[$id] = 'failure';
                        }
                        //冻结变动金额，为负数
                        $change_freeze_amount = -$cash_record->cash_apply_amount;
                        //添加钱包明细
                        $res = UserWalletLog::createWalletLog(
                            $cash_record->user_id,
                            0,
                            $change_freeze_amount,
                            UserWalletLog::TYPE_CASH_SUCCESS,
                            app('translator')->get('wallet.cash_success'),
                            $cash_record->id
                        );
                        if($res === false){
                            $db->rollBack();
                            return $status_result[$id] = 'failure';
                        }
                        $db->commit();
                        return $status_result[$id] = 'success';
                    }catch (\Exception $e){
                        $db->rollBack();
                        return $status_result[$id] = 'failure';
                    }
                }
            });
        return $this->outPut(ResponseCode::SUCCESS,'',$status_result);
    }
}
