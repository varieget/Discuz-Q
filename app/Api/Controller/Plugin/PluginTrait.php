<?php


namespace App\Api\Controller\Plugin;


use App\Common\PermissionKey;
use App\Common\Utils;
use App\Models\PluginGroupPermission;
use App\Models\PluginSettings;
use Symfony\Component\Finder\Finder;

trait PluginTrait
{
    private function getOneSettingAndConfig($appId, $isFromAdmin){
        $pluginList = \Discuz\Common\Utils::getPluginList();

        $setting = PluginSettings::getSettingRecord($appId);

        $setting = $this->getOutSetting($setting,$isFromAdmin);

        $data = [
            'setting'=>$setting,
            'config'=>$pluginList[$appId]??null
        ];

        return $data;
    }

    private function getAllSettingAndConfig($groupId, $isAdmin, $isFromAdmin){
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
            $pluginFiles = [];
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
                        if ($extension == 'js') {
                            $pluginFiles[$dirName]['js'][] = $fileUrl;
                        } else if ($extension == 'css') {
                            $pluginFiles[$dirName]['css'][] = $fileUrl;
                        } else {
                            $pluginFiles[$dirName]['assets'][] = $fileUrl;
                        }
                    }
                }
            }

            //前端插件入口
            $item['plugin_files'] = $pluginFiles;
            unset($item['plugin_' . $appId]);
            unset($item['busi']);

            if (isset($appSettingMap[$appId])){
                $item["setting"] = $this->getOutSetting($appSettingMap[$appId],$isFromAdmin);
            }else{
                $item["setting"] = [];
            }
        }

        return $pluginList;
    }

    private function getOutSetting($setting,$isFromAdmin){
        $privateValueData = $setting["private_value"];
        $publicValueData = $setting["public_value"];

        foreach ($privateValueData as $key=>$value){
            if (is_string($value)){
                $privateValueData[$key] = Utils::hideStr($value);
            }
        }

        $data = [];
        $data["id"] = $setting["id"];
        $data["appId"] = $setting["app_id"];
        $data["appName"] = $setting["app_name"];
        $data["type"] = $setting["type"];
        $data["publicValue"] = $publicValueData;
        $data["privateValue"] = $privateValueData;
        return $data;
    }
}
