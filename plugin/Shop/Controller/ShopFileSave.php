<?php


namespace Plugin\Shop\Controller;


use App\Common\ResponseCode;
use Discuz\Base\DzqLog;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Routing\UrlGenerator;
use League\Flysystem\Filesystem;

class ShopFileSave
{
    private $fileSystem;

    /** @var SettingsRepository $settings */
    private $settings;

    public function __construct(Factory $filesystem, SettingsRepository $settings){
        $this->fileSystem = $filesystem;
        $this->settings = $settings;
    }

    public function saveFile($fileName,$qrBuff){
        try {
            $path="shop/".$fileName;
            $isRemote=false;
            // 开启 cos 时，cos放一份
            DzqLog::error('ShopFileSave::saveFile', [], "1111");
            if($this->settings->get('qcloud_cos', 'qcloud')){
                DzqLog::error('ShopFileSave::saveFile', [], "1112");
                $this->fileSystem->disk('cos')->put("public/".$path, $qrBuff);
                $isRemote = true;
            }
            DzqLog::error('ShopFileSave::saveFile', [], "1113");
            $this->fileSystem->disk('public')->put($path, $qrBuff);
            DzqLog::error('ShopFileSave::saveFile', [], "1114");

            return [$path, $isRemote];
        } catch (Exception $e) {
            if (empty($e->validator) || empty($e->validator->errors())) {
                $errorMsg = $e->getMessage();
            } else {
                $errorMsg = $e->validator->errors()->first();
            }
            DzqLog::error('ShopFileSave::saveFile', [], $errorMsg);

            return ["",false];
        }
    }

    public function getFilePath($isRemote, $path){

        DzqLog::error('ShopFileSave::getFilePath', [], "2111");
        if($isRemote && $this->settings->get('qcloud_cos', 'qcloud')){
            DzqLog::error('ShopFileSave::getFilePath', [], "2112");
            $isExist = $this->fileSystem->disk('cos')->has("public/".$path);
            if ($isExist){
                DzqLog::error('ShopFileSave::getFilePath', [], "2113");
                $url = $this->fileSystem->disk('cos')->url("public/".$path);
                DzqLog::error('ShopFileSave::getFilePath', [], "2114");
                return $url;
            }
        }
        DzqLog::error('ShopFileSave::getFilePath', [], "2115");
        $url = $this->filesystem->disk('public')->url($path);
        DzqLog::error('ShopFileSave::getFilePath', [], "2116");
        return $url;
    }

    public function getCurrentUrl($urlOld){
        $isRemote = true;
        $qcloudIndex = strpos($urlOld,"myqcloud.com");
        if (!$qcloudIndex){
            $isRemote = false;
        }
        $pathIndex = strpos($urlOld,"public/shop");
        $path = substr($urlOld,$pathIndex+strlen("public/"));

        return $this->getFilePath($isRemote,$path);
    }
}
