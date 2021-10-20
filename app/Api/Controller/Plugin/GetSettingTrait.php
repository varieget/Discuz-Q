<?php


namespace App\Api\Controller\Plugin;


use App\Models\PluginSettings;
use App\Settings\SettingsRepository;

trait GetSettingTrait
{
    private function getResult($appId, $isAdmin){
        $pluginList = \Discuz\Common\Utils::getPluginList();
        $setting = PluginSettings::getSetting($appId);

        $this->checkResource($setting);
        $this->checkPublic($setting, $isAdmin);

        $data = [
            'setting'=>$setting,
            'config'=>$pluginList[$appId]??null
        ];

        return $data;
    }

    private function checkResource(&$setting){
        foreach ($setting as $key=>&$value){
            if(!is_array($value)){
                continue;
            }
            if (!isset($value["isResource"]) || $value["isResource"] == 0){
                continue;
            }
            //检查最新的url
            $url = $this->getCurrentUrl($value["value"]);
            $value["value"] = $url;
        }
    }

    private function checkPublic(&$setting, $isAdmin){
        if($this->user->isAdmin()){
            return;
        }

        $rmKey = [];
        foreach ($setting as $key=>$value){
            if(!is_array($value)){
                continue;
            }
            if (!isset($value["isPublic"]) || $value["isPublic"] != 0){
                continue;
            }

            $rmKey[] = $key;
        }

        foreach ($rmKey as $key){
            unset($setting[$key]);
        }
    }

    public function getCurrentUrl($urlOld){
        /** @var PluginFileSave $shopFileSave */
        $shopFileSave = $this->app->make(PluginFileSave::class);
        return $shopFileSave->getCurrentUrl($urlOld);
    }
}
