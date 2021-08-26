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

namespace App\Listeners\User;

use App\Models\Order;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserWalletCash;
use App\Settings\SettingsRepository;
use App\Events\Setting\Saved;
use Illuminate\Support\Arr;
use Discuz\Qcloud\QcloudTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Ms\V20180408\Models\DescribeUserBaseInfoInstanceRequest;
use TencentCloud\Ms\V20180408\MsClient;
use function Clue\StreamFilter\fun;

class QcloudSiteInfoDaily
{
    use QcloudTrait;

    /**
     * @var Request
     */
    public $request;

    /**
     * @var SettingsRepository
     */
    public $settings;

    /**
     * @param Request $request
     * @param SettingsRepository $settings
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    public function handle()
    {
        $tomorrow = date("Y-m-d",strtotime("+1 day"));
        $cache_time = strtotime($tomorrow) - time();
        $uin = app('cache')->get('qcloud_uin');
        $settings = app('cache')->get('settings_up');
        $isset_daily = app('cache')->get('qcloud_site_info_daily_'.$settings['site_id']);
        if($isset_daily){
            return;
        }
        $site_url = !empty($settings['site_url']) ? $settings['site_url'] : '';
        $appfile = base_path('vendor/discuz/core/src/Foundation/Application.php');
        $current_version_time = date('Y-m-d H:i:s', filemtime($appfile));
        if(empty($settings['site_init_version'])){
            $settings['site_init_version'] = app()->version();
            $this->settings->set('site_init_version', $settings['site_init_version'], 'default');
        }
        if(empty($settings['site_init_version_time'])){
            $settings['site_init_version_time'] = $current_version_time;
            $this->settings->set('site_init_version_time', $settings['site_init_version_time'], 'default');
        }
        $withdrawal_profit = round(UserWalletCash::query()->where('cash_status', UserWalletCash::STATUS_PAID)->sum('cash_charge'), 2);
        $order_royalty = round(Order::query()->where('status', Order::ORDER_STATUS_PAID)->sum('master_amount'), 2);
        $total_register_profit = round(Order::query()->where('type', Order::ORDER_TYPE_REGISTER)->where('status', Order::ORDER_STATUS_PAID)->sum('amount'), 2);
        $total_profit = $withdrawal_profit + $order_royalty + $total_register_profit;

        $json = [
            'site_id' => $settings['site_id'] ?? '',
            'site_secret' => !empty($settings['site_secret']) ? $settings['site_secret'] : '',
            'site_name' =>  !empty($settings['site_name']) ? $settings['site_name'] : '',
            'site_url'  =>  $settings['site_url'],
            'site_ip'   =>  !empty($site_url) ? gethostbyname($site_url) : '',
            'site_charge'    =>  !empty($settings['site_price']) ? 1 : 0,
            'qcloud_secret_id'  =>  !empty($settings['qcloud_secret_id']) ? $settings['qcloud_secret_id'] : '-',
            'site_uin'  =>  $uin,
            'relation_qcloud'   =>  !empty($settings['qcloud_secret_id']) ? 1 : 0,
            'qcloud_secret_init_time'   =>  $settings['qcloud_secret_init_time'] ?? null,
            'current_version'   =>  app()->version(),
            'current_version_time'  =>  $current_version_time,
            'site_init_version' =>  $settings['site_init_version'],
            'site_init_version_time' =>  $settings['site_init_version_time'],
            'miniprogram_open'     =>  !empty($settings['miniprogram_close']) ? 1 : 0,
            'offiaccount_open'     =>  !empty($settings['offiaccount_close']) ? 1 : 0,
            'web_open'  =>  empty($settings['site_close']) ? 1 : 0,
            'withdrawal_profit' =>  $withdrawal_profit,
            'order_royalty' =>  $order_royalty,
            'total_register_profit' =>  $total_register_profit,
            'total_profit' =>  $total_profit,
            'total_user_count'  =>  User::query()->count(),
            'total_thread_count'    =>  Thread::query()->count(),
            'total_post_count'  =>  Post::query()->count()
        ];
        try {
            $this->siteInfoDaily($json)->wait();
            app('cache')->put('qcloud_site_info_daily_'.$settings['site_id'] , 1, $cache_time);
        }catch (\Exception $e){

        }

    }

}
