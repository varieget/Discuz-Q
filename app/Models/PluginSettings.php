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

namespace App\Models;

use Carbon\Carbon;
use Discuz\Base\DzqModel;


/**
 * @property int $id
 * @property string $app_id
 * @property string $app_name
 * @property int $type
 * @property string $private_value
 * @property string $public_value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PluginSettings extends DzqModel
{
    protected $table = 'plugin_settings';

    public static function getSettingRecord($appId)
    {
        $setting = PluginSettings::query()->where(['app_id' => $appId])->first();
        if (empty($setting)) return [];

        if(!empty($setting['private_value'])){
            $setting['private_value'] = json_decode($setting['private_value'], true);
        }else{
            $setting['private_value'] = [];
        }
        if(!empty($setting['public_value'])){
            $setting['public_value'] = json_decode($setting['public_value'], true);
        }else{
            $setting['public_value'] = [];
        }

        return $setting;
    }

    public static function getSetting($appId)
    {
        $setting = PluginSettings::query()->where(['app_id' => $appId])->first();
        if (empty($setting)) return [];

        $result = [];
        if(!empty($setting['private_value'])){
            $privateValue = json_decode($setting['private_value'], true);
            $result = $privateValue;
        }
        if(!empty($setting['public_value'])){
            $publicValue = json_decode($setting['public_value'], true);
            $result = array_merge($result,$publicValue);
        }

        return $result;
    }

    public static function getAllSettingRecord()
    {
        $appSettingMap = PluginSettings::query()->get()->keyBy("app_id")->toArray();
        foreach ($appSettingMap as $key=>&$setting){
            if(!empty($setting['private_value'])){
                $setting['private_value'] = json_decode($setting['private_value'], true);
            }else{
                $setting['private_value'] = [];
            }
            if(!empty($setting['public_value'])){
                $setting['public_value'] = json_decode($setting['public_value'], true);
            }else{
                $setting['public_value'] = [];
            }
        }
        return $appSettingMap;
    }
}
