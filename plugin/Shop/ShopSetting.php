<?php


namespace Plugin\Shop;


use Plugin\Shop\Controller\WxShopTrait;

class ShopSetting
{
    use WxShopTrait;
    private $settingValue = [
        "isOpen"=>1,
        "wxAppId"=>"",
        "wxAppSecret"=>"",
        "wxQrcode"=>"",
        "description"=>"",
        "wxScheme"=>""
    ];

    public function setSetting(&$privateValue,&$publicValue){
        $value = array_merge($privateValue,$publicValue);
        if(empty($value["wxAppId"]) || empty($value["wxAppSecret"])){
            $publicValue["wxScheme"] = "";
        }

        $scheme = $this->getScheme($value["wxAppId"],$value["wxAppSecret"],null,"");
        $publicValue["wxScheme"] = $scheme;
    }

}
