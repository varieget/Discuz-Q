<?php


namespace Plugin\Shop\Controller;

use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Illuminate\Support\Arr;

class ShopUploadImageController extends DzqController
{
    use WxShopTrait;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return $this->checkPermission($userRepo);
    }

    public function main()
    {
        $file = Arr::get($this->request->getUploadedFiles(), 'file');
        if (empty($file)){
            return $this->outPut(ResponseCode::INVALID_PARAMETER);
        }
        $baseName = pathinfo($file,PATHINFO_BASENAME);
        $ext = pathinfo($file,PATHINFO_EXTENSION);
        $baseNameTemp = $baseName."_".time().".".$ext;

        /** @var ShopFileSave $shopFileSave */
        $shopFileSave = $this->app->make(ShopFileSave::class);
        list($path,$isRemote) = $shopFileSave->saveFile($baseNameTemp,$file);
        $pathUrl = $shopFileSave->getFilePath($isRemote,$path);
        $result = [];
        $result["url"] = $pathUrl;

       $this->outPut(0,'', $result);
    }
}
