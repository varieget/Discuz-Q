<?php


namespace Plugin\Shop\Controller;

use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Stream;

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

        /** @var Stream $fileContent */
        $fileContent = $file->getStream();
        $fileName = $file->getClientFilename();
        $ext = pathinfo($fileName,PATHINFO_EXTENSION);
        $fileName = md5($fileName).time().".".$ext;

        /** @var ShopFileSave $shopFileSave */
        $shopFileSave = $this->app->make(ShopFileSave::class);
        list($path,$isRemote) = $shopFileSave->saveFile($fileName,$fileContent->getContents());
        $pathUrl = $shopFileSave->getFilePath($isRemote,$path);
        $result = [];
        $result["url"] = $pathUrl;

       $this->outPut(0,'', $result);
    }
}
