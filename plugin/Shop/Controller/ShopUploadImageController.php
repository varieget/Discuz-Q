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

        $fileContent = $file->getStream();
        $filePathTemp = $fileContent->getMetadata('uri');
        $fileName = $file->getClientFilename();
        $ext = pathinfo($fileName,PATHINFO_EXTENSION);
        $fileName = md5($fileName).time().".".$ext;
        $fileType = $file->getClientMediaType();

        /** @var ShopFileSave $shopFileSave */
        $shopFileSave = $this->app->make(ShopFileSave::class);
        list($path,$isRemote) = $shopFileSave->saveFile($fileName,$fileContent);
        $pathUrl = $shopFileSave->getFilePath($isRemote,$path);
        $result = [];
        $result["url"] = $pathUrl;

       $this->outPut(0,'', $result);
    }
}
