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
    private const COS_PATH="public/shop";

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
            // 开启 cos 时
            if($this->settings->get('qcloud_cos', 'qcloud')){
                $this->fileSystem->disk('cos')->put("public/".$path, $qrBuff);
                $isRemote = true;
            }
            $this->fileSystem->disk('public')->put($path, $qrBuff);

            return ["public/".$path, $isRemote];
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

        if($isRemote && $this->settings->get('qcloud_cos', 'qcloud')){

            $xx = $this->fileSystem->disk('cos');

            $url = $this->fileSystem->disk('cos')->url($path);

        }else{
            $url = $this->filesystem->disk('public')->url($path);
        }
        return $url;
    }
}
