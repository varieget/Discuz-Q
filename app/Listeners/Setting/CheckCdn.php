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

namespace App\Listeners\Setting;

use App\Api\Controller\SettingsV3\CdnTrait;
use App\Api\Controller\SettingsV3\DnspodTrait;
use App\Events\Setting\Saving;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Support\Arr;
use Illuminate\Validation\Factory as Validator;
use Illuminate\Validation\ValidationException;

class CheckCdn
{
    use CdnTrait;
    use DnspodTrait;

    /**
     * @var SettingsRepository
     */
    public $settings;

    /**
     * @var Validator
     */
    public $validator;

    /**
     * @param SettingsRepository $settings
     * @param Validator $validator
     */
    public function __construct(SettingsRepository $settings, Validator $validator)
    {
        $this->settings = $settings;
        $this->validator = $validator;
    }

    /**
     * @param Saving $event
     * @throws ValidationException
     */
    public function handle(Saving $event)
    {
        $settings = $event->settings->where('tag', 'qcloud')->pluck('value', 'key')->toArray();

        if (Arr::hasAny($settings, [
            'qcloud_cdn_speed_domain',
            'qcloud_cdn_main_domain',
            'qcloud_cdn_origins',
            'qcloud_cdn_server_name',
        ])) {
            // 合并原配置与新配置（新值覆盖旧值）
            $settings = array_merge((array) $this->settings->tag('qcloud'), $settings);

            $this->validator->make($settings, [
                'qcloud_cdn_speed_domain' => 'string|required',
                'qcloud_cdn_main_domain' => 'string|required',
                'qcloud_cdn_origins' => 'required',
                'qcloud_cdn_server_name' => 'string|required',
            ])->validate();

            if (is_array($settings['qcloud_cdn_origins'])) {
                $event->settings->put('qcloud_qcloud_cdn_origins', [
                    'key' => 'qcloud_cdn_origins',
                    'value' => json_encode($settings['qcloud_cdn_origins']),
                    'tag' => 'qcloud'
                ]);
            }

            $isAdd = false;
            if (empty($this->settings->get('qcloud_cdn_speed_domain', 'qcloud'))) {
                $isAdd = true;
            }
            $this->saveCdnDomain($settings, $isAdd);
        }
    }

    public function saveCdnDomain($settings, $isAdd)
    {
        $speedDomain = isset($settings['qcloud_cdn_speed_domain']) ? $settings['qcloud_cdn_speed_domain'] : '';
        $mainDomain = isset($settings['qcloud_cdn_main_domain']) ? $settings['qcloud_cdn_main_domain'] : '';
        $cdnOrigins = isset($settings['qcloud_cdn_origins']) ? $settings['qcloud_cdn_origins'] : [];
        $cdnServerName = isset($settings['qcloud_cdn_server_name']) ? $settings['qcloud_cdn_server_name'] : '';

        if (!$isAdd) {
            $this->startCdnDomain($speedDomain);
            $this->updateCdnDomain($speedDomain, $cdnOrigins, $cdnServerName);
        } else {
            $this->addCdnDomain($speedDomain, $cdnOrigins, $cdnServerName);

            // 添加域名、解析
            $this->createDomain($mainDomain);
            $domains = $this->describeDomains($speedDomain);
            $cname = '';
            if (isset($domains['TotalNumber']) && $domains['TotalNumber'] == 1) {
                $cname = $domains['Domains'][0]['Cname'];
            }
            $this->createRecord($mainDomain, $cname);
        }

        if (isset($settings['qcloud_cdn']) && (bool)$settings['qcloud_cdn'] == true) { //开启了cdn
            $this->startCdnDomain($speedDomain);
        } else {
            $this->stopCdnDomain($speedDomain);
        }
    }
}
