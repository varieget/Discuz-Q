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

namespace Plugin\Activity\Model;


use Discuz\Base\DzqModel;

class ThreadActivity extends DzqModel
{
    protected $table='plugin_activity_thread_activity';

    const ADDITIONAL_INFO_TYPE_NAME = 1;
    const ADDITIONAL_INFO_TYPE_MOBILE = 2;
    const ADDITIONAL_INFO_TYPE_N_M = 3;
    const ADDITIONAL_INFO_TYPE_WEIXIN = 4;
    const ADDITIONAL_INFO_TYPE_N_W = 5;
    const ADDITIONAL_INFO_TYPE_M_W = 6;
    const ADDITIONAL_INFO_TYPE_N_M_W = 7;
    const ADDITIONAL_INFO_TYPE_AD = 8;
    const ADDITIONAL_INFO_TYPE_AD_N = 9;
    const ADDITIONAL_INFO_TYPE_AD_M = 10;
    const ADDITIONAL_INFO_TYPE_AD_N_M = 11;
    const ADDITIONAL_INFO_TYPE_AD_W = 12;
    const ADDITIONAL_INFO_TYPE_AD_N_W = 13;
    const ADDITIONAL_INFO_TYPE_AD_M_W = 14;
    const ADDITIONAL_INFO_TYPE_AD_N_M_W = 15;

    public static function allowInfoType(){
        return  [
            0,
            self::ADDITIONAL_INFO_TYPE_NAME,
            self::ADDITIONAL_INFO_TYPE_MOBILE,
            self::ADDITIONAL_INFO_TYPE_N_M,
            self::ADDITIONAL_INFO_TYPE_WEIXIN,
            self::ADDITIONAL_INFO_TYPE_N_W,
            self::ADDITIONAL_INFO_TYPE_M_W,
            self::ADDITIONAL_INFO_TYPE_N_M_W,
            self::ADDITIONAL_INFO_TYPE_AD,
            self::ADDITIONAL_INFO_TYPE_AD_N,
            self::ADDITIONAL_INFO_TYPE_AD_M,
            self::ADDITIONAL_INFO_TYPE_AD_N_M,
            self::ADDITIONAL_INFO_TYPE_AD_W,
            self::ADDITIONAL_INFO_TYPE_AD_N_W,
            self::ADDITIONAL_INFO_TYPE_AD_M_W,
            self::ADDITIONAL_INFO_TYPE_AD_N_M_W,
        ];
    }

}
