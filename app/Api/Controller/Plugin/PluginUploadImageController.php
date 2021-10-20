<?php


namespace App\Api\Controller\Plugin;

use App\Common\ResponseCode;
use Discuz\Base\DzqAdminController;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Stream;

class PluginUploadImageController extends DzqAdminController
{
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

        /** @var PluginFileSave $shopFileSave */
        $shopFileSave = $this->app->make(PluginFileSave::class);
        list($path,$isRemote) = $shopFileSave->saveFile($fileName,$fileContent->getContents());
        $pathUrl = $shopFileSave->getFilePath($isRemote,$path);
        $result = [];
        $result["url"] = $pathUrl;

       $this->outPut(0,'', $result);
    }
}
