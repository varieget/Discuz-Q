<?php


namespace Plugin\Shop\Controller;

use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;

class WxShopInfoController extends DzqController
{
    use WxShopTrait;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return $this->checkPermission($userRepo);
    }

    public function main()
    {
        $setting = $this->getSetting();
        if (!$setting){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND,"没有配置");
        }
        if (!isset($setting["wx_app_id"])){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND,"配置不全");
        }

        $qrUrl = "";
        if (isset($setting["wx_qrcode"])){
            $urlOld = $setting["wx_qrcode"];
            /** @var ShopFileSave $shopFileSave */
            $shopFileSave = $this->app->make(ShopFileSave::class);
            $qrUrl = $shopFileSave->getCurrentUrl($urlOld);
        }

        $result = [];
        $result["wx_app_id"] = $setting["wx_app_id"];
        $result["wx_qrcode"] = $qrUrl;

       $this->outPut(0,'',$result);
    }
}
