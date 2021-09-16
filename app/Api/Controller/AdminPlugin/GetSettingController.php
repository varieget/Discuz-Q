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

namespace App\Api\Controller\AdminPlugin;

use App\Common\Utils;
use App\Models\PluginSettings;
use Discuz\Base\DzqAdminController;

class GetSettingController extends DzqAdminController
{
    public function main()
    {
        $appId = $this->inPut('appId');
        $this->dzqValidate(['appId' => $appId], ['appId' => 'required|string']);
        $pluginList = Utils::getPluginList();
        $setting = PluginSettings::getSetting($appId);
        $data = [
            'setting'=>$setting,
            'config'=>$pluginList[$appId]?:null
        ];
        $this->outPut(0,'',$data);
    }
}
