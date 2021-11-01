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

namespace App\Api\Controller\Plugin;

use App\Common\PermissionKey;
use App\Common\Utils;
use App\Models\PluginGroupPermission;
use App\Models\PluginSettings;
use Symfony\Component\Finder\Finder;

trait PluginTrait
{
    private function getOneSettingAndConfig($appId, $isFromAdmin)
    {
        $pluginList = \Discuz\Common\Utils::getPluginList();

        $setting = PluginSettings::getSettingRecord($appId);

        $setting = $this->getOutSetting($setting, $isFromAdmin);

        $data = [
            'setting'=>$setting,
            'config'=>$pluginList[$appId]??null
        ];

        return $data;
    }

    private function getAllSettingAndConfig($groupId, $isAdmin, $isFromAdmin)
    {
        $pluginList = \Discuz\Common\Utils::getPluginList();
        $permissions = PluginGroupPermission::query()
            ->where('group_id', $groupId)->get()->keyBy('app_id')->toArray();

        $appSettingMap = app()->make(PluginSettings::class)->getAllSettingRecord();

        foreach ($pluginList as &$item) {
            $permission = $permissions[$item['app_id']] ?? null;
            $appId = $item['app_id'];
            $appName = $item['name_en'];
            $pluginDirectories = $item['plugin_' . $appId];
            //当前登录用户权限
            $item['authority'] = [
                'title' => '插入' . $item['name_cn'],
                'permission' => PermissionKey::PLUGIN_INSERT_PERMISSION,
                'canUsePlugin' => $isAdmin ? true : (empty($permission) ? false : ($permission['status'] ? true : false)),
            ];
            $distPath = $pluginDirectories['view'] . DIRECTORY_SEPARATOR . 'dist';
            if (is_dir($distPath)) {
                $dirs = Finder::create()->in($distPath)->directories();
                foreach ($dirs as $dir) {
                    $dirPath = $dir->getPathname();
                    $dirName = $dir->getFilename();
                    $files = Finder::create()->in($dirPath)->files();
                    foreach ($files as $file) {
                        $fileName = $file->getFilename();
                        $extension = strtolower($file->getExtension());
                        $fileUrl = Utils::getDzqDomain() . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR . $appName . DIRECTORY_SEPARATOR . $dirName . DIRECTORY_SEPARATOR . $fileName;
                        if (isset($item['view'])) {
                            if (isset($item['view'][$dirName])) {
                                if ($extension == 'js') {
                                    $item['view'][$dirName]['pluginFiles']['js'][] = $fileUrl;
                                } elseif ($extension == 'css') {
                                    $item['view'][$dirName]['pluginFiles']['css'][] = $fileUrl;
                                } else {
                                    $item['view'][$dirName]['pluginFiles']['assets'][] = $fileUrl;
                                }
                            } else {
                                throw new \Exception('view file directory' . $dirName . 'not exist');
                            }
                        }
                    }
                }
            }
            unset($item['plugin_' . $appId]);
            unset($item['busi']);
            if (isset($appSettingMap[$appId])) {
                $item['setting'] = $this->getOutSetting($appSettingMap[$appId], $isFromAdmin);
            } else {
                $item['setting'] = [];
            }
        }

        return $pluginList;
    }

    private function getInSetting(&$private_value, $public_value)
    {
        /** @var PluginFileSave $shopFileSave */
        $shopFileSave = $this->app->make(PluginFileSave::class);
        $resourceKeys = [];
        foreach ($private_value as $key=>$value) {
            $urlTemp = parse_url($value);
            if (isset($urlTemp['scheme']) && isset($urlTemp['host'])) {
                $isResource = $shopFileSave->checkSiteResource($value);
                if ($isResource) {
                    $resourceKeys[]=$key;
                }
            }
        }
        foreach ($public_value as $key=>$value) {
            $urlTemp = parse_url($value);
            if (isset($urlTemp['scheme']) && isset($urlTemp['host'])) {
                $isResource = $shopFileSave->checkSiteResource($value);
                if ($isResource) {
                    $resourceKeys[]=$key;
                }
            }
        }
        $private_value['resourceKeys'] = $resourceKeys;
        return true;
    }

    private function getOutSetting($setting, $isFromAdmin)
    {
        $privateValueData = $setting['private_value'];
        $publicValueData = $setting['public_value'];

        if (isset($privateValueData['resourceKeys'])) {
            foreach ($privateValueData['resourceKeys'] as $key) {
                if (isset($privateValueData[$key])) {
                    $urlOld = $privateValueData[$key];
                    $urlNew = $this->getCurrentUrl($urlOld);
                    $privateValueData[$key] = $urlNew;
                } elseif (isset($publicValueData[$key])) {
                    $urlOld = $publicValueData[$key];
                    $urlNew = $this->getCurrentUrl($urlOld);
                    $publicValueData[$key] = $urlNew;
                }
            }
            unset($privateValueData['resourceKeys']);
        }

        foreach ($privateValueData as $key=>$value) {
            if (is_string($value)) {
                $privateValueData[$key] = Utils::hideStr($value);
            }
        }

        $data = [];
        $data['id'] = $setting['id'];
        $data['appId'] = $setting['app_id'];
        $data['appName'] = $setting['app_name'];
        $data['type'] = $setting['type'];
        $data['publicValue'] = $publicValueData;
        $data['privateValue'] = $privateValueData;
        return $data;
    }

    public function getCurrentUrl($urlOld)
    {
        /** @var PluginFileSave $shopFileSave */
        $shopFileSave = $this->app->make(PluginFileSave::class);
        return $shopFileSave->getCurrentUrl($urlOld);
    }
}
