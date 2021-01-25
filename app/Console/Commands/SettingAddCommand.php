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

use App\Models\Setting;
use App\Models\Thread;
use Discuz\Console\AbstractCommand;

class SettingAddCommand extends AbstractCommand
{
    protected $signature = 'setting:add';

    protected $description = '增加内容付费网站初始设置';

    public function handle()
    {
        $newSetting = [
            'site_open_sort' => 0,
            'site_create_thread' . Thread::TYPE_OF_TEXT => 1,
            'site_create_thread' . Thread::TYPE_OF_LONG => 1,
            'site_create_thread' . Thread::TYPE_OF_VIDEO => 1,
            'site_create_thread' . Thread::TYPE_OF_IMAGE => 1,
            'site_create_thread' . Thread::TYPE_OF_AUDIO => 1,
            'site_create_thread' . Thread::TYPE_OF_QUESTION => 1,
            'site_create_thread' . Thread::TYPE_OF_GOODS => 1,
            'site_skin' => 1
        ];

        $setting = Setting::query()->get();
        $setting = $setting->toArray();

        $existSetting = array();
        foreach ($setting as $setting_key => $setting_val) {
            array_push($existSetting ,$setting_val['key']);
        }

        foreach ($newSetting as $key => $val) {
            if (!in_array($key, $existSetting)) {
                Setting::query()->insert(['key' => $key, 'value' => $val, 'tag' => 'default']);
            }
        }

    }
}
