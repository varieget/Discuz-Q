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

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = new Setting();
        $settings->truncate();
        $settings->insert([
            [
                'key' => 'site_close',          // 站点开关：0 开启站点，1 关闭站点
                'value' => '0',                 // 默认开启
                'tag' => 'default',
            ],
            [
                'key' => 'site_mode',           // 站点模式：public 公开，pay 付费
                'value' => 'public',            // 默认公开
                'tag' => 'default',
            ],
            [
                'key' => 'site_author',         // 站长用户ID 1 管理员
                'value' => '1',                 // 默认用户1
                'tag' => 'default',
            ],
            [
                'key' => 'site_master_scale',   // 站长分成比例
                'value' => '0',                 // 默认 0
                'tag' => 'default',
            ],
            [
                'key' => 'site_author_scale',   // 用户分成比例
                'value' => '10',                // 默认 10
                'tag' => 'default',
            ],
            [
                'key' => 'register_close',      // 注册开关：0 禁止，1 允许
                'value' => '1',                 // 默认允许
                'tag' => 'default',
            ],
            [
                'key' => 'register_validate',   // 注册审核：0关闭，1开启
                'value' => '0',                 // 默认关闭
                'tag' => 'default',
            ],
            [
                'key' => 'qcloud_sms',          // 腾讯云短信开关：0 关闭，1 开启
                'value' => '0',                 // 默认关闭
                'tag' => 'qcloud',
            ],
            [
                'key' => 'qcloud_vod',          // 腾讯云点播开关：0 关闭，1 开启
                'value' => '0',                 // 默认关闭
                'tag' => 'qcloud',
            ],
            [
                'key' => 'support_img_ext',     // 默认支持的图片扩展名
                'value' => 'png,gif,jpg,jpeg,heic',
                'tag' => 'default',
            ],
            [
                'key' => 'support_file_ext',    // 默认支持的附件扩展名
                'value' => 'doc,docx,pdf,zip',
                'tag' => 'default',
            ],
            [
                'key' => 'support_max_size',    // 默认支持附件最大大小 MB单位
                'value' => 5,
                'tag' => 'default',
            ],
            [
                'key' => 'miniprogram_video',   // 小程序视频开关：0 关闭，1 开启
                'value' => 0,                   // 默认开启
                'tag' => 'wx_miniprogram',
            ],
            [
                'key' => 'site_open_sort',    // 是否开启智能排序，0不开启，1开启
                'value' => 0,
                'tag' => 'default',
            ],
            [
                'key' => 'site_create_thread',    // 允许发布帖子，0为不允许，1为允许，以下一样
                'value' => 1,
                'tag' => 'default',
            ],
            [
                'key' => 'site_create_thread0',    // 允许插入图片
                'value' => 1,
                'tag' => 'default',
            ],
            [
                'key' => 'site_create_thread1',    // 允许插入视频
                'value' => 1,
                'tag' => 'default',
            ],
            [
                'key' => 'site_create_thread2',    //允许插入语音
                'value' => 1,
                'tag' => 'default',
            ],
            [
                'key' => 'site_create_thread3',    // 允许插入附件
                'value' => 1,
                'tag' => 'default',
            ],
            [
                'key' => 'site_create_thread4',    // 允许插入商品
                'value' => 0,
                'tag' => 'default',
            ],
            [
                'key' => 'site_create_thread5',    // 允许插入付费
                'value' => 1,
                'tag' => 'default',
            ],
            [
                'key' => 'site_create_thread6',    // 允许插入悬赏
                'value' => 1,
                'tag' => 'default',
            ],
            [
                'key' => 'site_create_thread7',    // 允许插入红包
                'value' => 1,
                'tag' => 'default',
            ],
            [
                'key' => 'site_create_thread8',    // 允许插入位置
                'value' => 1,
                'tag' => 'default',
            ],
            [
                'key' => 'site_manage',          // 站点开关：0 开启站点，1 关闭站点
                'value' => '[{"key":1,"desc":"PC端","value":true},{"key":2,"desc":"H5端","value":true},{"key":3,"desc":"小程序端","value":true}]',                 // 默认开启
                'tag' => 'default',
            ],
            [
                'key' => 'api_freq',    // 允许发布商品帖
                'value' => '{"get":{"freq":500,"forbidden":20},"post":{"freq":200,"forbidden":30}}',
                'tag' => 'default',
            ]
        ]);
    }
}
