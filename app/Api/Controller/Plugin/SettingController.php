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

namespace App\Api\Controller\Plugin;

use App\Common\ResponseCode;
use App\Models\PluginSettings;
use Discuz\Base\DzqAdminController;

class SettingController extends DzqAdminController
{
    public function main()
    {
        $appId = $this->inPut('appId');
        $name = $this->inPut('appName');
        $type = $this->inPut('type');
        $value = $this->inPut('value');
        $this->dzqValidate($this->inPut(), [
            'appId' => 'required|string|max:100',
            'appName' => 'required|string|max:100',
            'type' => 'required|integer',
            'value' => 'required|array'
        ]);
        $pluginSetting = PluginSettings::query()->where(['app_id' => $appId])->first();
        if (empty($pluginSetting)) {
            $pluginSetting = new PluginSettings();
        }
        $pluginSetting->app_id = $appId;
        $pluginSetting->app_name = $name;
        $pluginSetting->type = $type;
        $pluginSetting->value = json_encode($value, 256);
        if (!$pluginSetting->save()) {
            $this->outPut(ResponseCode::DB_ERROR);
        }
        $this->outPut(0);
    }
}
