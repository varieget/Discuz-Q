<?php


namespace Plugin\Shop\Controller;

use App\Common\ResponseCode;
use App\Common\Utils;
use App\Models\PluginSettings;
use Discuz\Base\DzqAdminController;

class WxShopSettingController extends DzqAdminController
{
    use WxShopTrait;

    public function main()
    {
       $appid = Utils::getAppKey("plugin_appid");
       list($path,$isRemote) = $this->getShopQrCode($appid);
       if (empty($path)){
           $this->outPut(ResponseCode::RESOURCE_NOT_FOUND,"生成二维码异常，请检查配置");
       }
       $url = $this->getQRUrl($isRemote,$path);

       /** @var PluginSettings $pluginSettings */
       $pluginSettings = app()->make(PluginSettings::class);

       $settingData = $pluginSettings->getSettingRecord($appid);
       $settingData["public_value"]["wxQrcode"] = $url;
       $pluginSettings->setData($appid, $settingData["app_name"], $settingData["type"],
            $settingData["private_value"], $settingData["public_value"]);

       $data = [];
       $data['wxQrCode'] = $url;

       $this->outPut(0,'',$data);
    }
}
