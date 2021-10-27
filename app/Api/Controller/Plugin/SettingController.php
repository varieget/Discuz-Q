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
    use PluginTrait;

    public function main()
    {
        $appId = $this->inPut('appId');
        $name = $this->inPut('appName');
        $type = $this->inPut('type');
        $privateValue = $this->inPut('privateValue');
        $publicValue = $this->inPut('publicValue');
        $this->dzqValidate($this->inPut(), [
            'appId' => 'required|string|max:100',
            'appName' => 'required|string|max:100',
            'type' => 'required|integer',
        ]);

        if (!is_array($privateValue) || !is_array($privateValue)){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }

        $intersectKeys = array_intersect_key($privateValue,$publicValue);
        if (!empty($intersectKeys)){
            $this->outPut(ResponseCode::INVALID_PARAMETER,"key重复");
        }

        $pluginSetting = PluginSettings::query()->where(['app_id' => $appId])->first();
        if (empty($pluginSetting)) {
            $pluginSetting = new PluginSettings();
        }
        $pluginSetting->app_id = $appId;
        $pluginSetting->app_name = $name;
        $pluginSetting->type = $type;

        $result = $this->getInSetting($privateValue,$publicValue);
        if (!$result){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }

        $this->pluginSetting($appId,$privateValue,$publicValue);


        $pluginSetting->private_value = json_encode($privateValue, 256);
        $pluginSetting->public_value = json_encode($publicValue, 256);

        if (!$pluginSetting->save()) {
            $this->outPut(ResponseCode::DB_ERROR);
        }
        $this->outPut(0);
    }

    private function pluginSetting($appId, &$privateValue,&$publicValue){
        $pluginList = \Discuz\Common\Utils::getPluginList();
        if (empty($pluginList[$appId]) || empty($pluginList[$appId]['settingBusi'])){
            return;
        }

        $busiClass = $pluginList[$appId]['settingBusi'];
        $busi = new \ReflectionClass($busiClass);
        $busiObj = $busi->newInstanceArgs([]);
        if(!method_exists($busiObj,"setSetting")){
            return;
        }
        $busiObj->setSetting($privateValue, $publicValue);
    }
}
