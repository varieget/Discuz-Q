<?php
/**
 * Copyright (C) 2021 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Plugin\Shop\Controller;


use App\Common\ResponseCode;
use App\Models\PluginSettings;
use App\Models\Setting;
use Discuz\Base\DzqLog;
use Discuz\Wechat\EasyWechatTrait;
use GuzzleHttp\Client;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Facades\App;

trait WxShopTrait
{
    use EasyWechatTrait;

    protected $config;
    protected $httpClient;

    public function checkPermission($userRepo, $guestEnable = false)
    {
//        if (!$this->user->isAdmin()){
//            return false;
//        }
        return true;
    }


    public function getConfig(){
        if (empty($this->config)){
            $config = json_decode(file_get_contents(__DIR__ . "/../config.json"), true);
            $this->config = $config;
        }
        return $this->config;
    }

    /**
     * @return ["wxAppId","wxAppSecret","wxQrcode"]
     */
    public function getSetting(){
        $this->getConfig();

        $settingData = PluginSettings::query()->where("app_id",$this->config["app_id"])->first();
        if (empty($settingData)){
           return false;
        }
        if (empty($settingData->value)){
            return false;
        }

        $valueJson = json_decode($settingData->value,true);

        return $valueJson;
    }

    public function getWxApp(){
        DzqLog::error("gjz 001 ",[],"100001");
        $settingData = $this->getSetting();
        if (empty($settingData)){
            DzqLog::error("gjz 001 ",[],"100002");
            return [ResponseCode::RESOURCE_NOT_FOUND,"插件没配置"];
        }
        DzqLog::error("gjz 001 ",[],"100003=".json_encode($settingData));
        if (!isset($settingData["wxAppId"])
            || !isset($settingData["wxAppId"]["value"])
            || !isset($settingData["wxAppSecret"])
            || !isset($settingData["wxAppSecret"]["value"])){
            DzqLog::error("gjz 001 ",[],"100004");
            return [ResponseCode::RESOURCE_NOT_FOUND,"插件没配置"];
        }
        DzqLog::error("gjz 001 ",[],"100005");
        return [0, $this->miniProgram(["app_id"=>$settingData["wxAppId"]["value"],"secret"=>$settingData["wxAppSecret"]["value"]])];
    }

    public function getAccessToken(){
        DzqLog::error("gjz 002 ",[],"100001");
        list($result,$wxApp) = $this->getWxApp();
        if ($result !== 0){
            DzqLog::error("gjz 002 ",[],"100002");
            return [$result,$wxApp];
        }
        DzqLog::error("gjz 002 ",[],"100003");
        $accessToken = $wxApp->access_token->getToken(true);
        if (empty($accessToken["access_token"])){
            DzqLog::error("gjz 002 ",[],"100004");
            return [ResponseCode::RESOURCE_NOT_FOUND,"插件配置错误"];
        }
        DzqLog::error("gjz 002 ",[],"100005");
        return [0,$accessToken["access_token"]];
    }


    private function getShopList($accessToken,$page,$perPage)
    {
        $url = "https://api.weixin.qq.com/product/spu/get_list?access_token=".$accessToken;
        $one = new Client([]);

        $body = [ "status"=>5,
            "page"=>$page,
            "page_size"=>$perPage,
            "need_edit_spu"=>0
        ];
        $bodyStr = json_encode($body);

        $options = [
                "body"=>$bodyStr
            ];

        $response = $one->request("post", $url, $options);
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND,"商品列表错误");
        }
        $contentData = $response->getBody()->getContents();
        if (empty($contentData)){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND,"商品列表错误");
        }
        $result = json_decode($contentData,true);

        return $result;
    }

    private function getProductInfo($accessToken, $productId)
    {
        $url = "https://api.weixin.qq.com/product/spu/get?access_token=".$accessToken;

        if(empty($this->httpClient)){
            $this->httpClient = new Client([]);
        }

        $body = [ "product_id"=>$productId,
            "out_product_id"=>"",
            "need_edit_spu"=>0
        ];
        $bodyStr = json_encode($body);

        $options = [
            "body"=>$bodyStr
        ];

        $response = $this->httpClient->request("post", $url, $options);

        $statusCode = $response->getStatusCode();
        if ($statusCode != 200){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND,"商品错误");
        }
        $contentData = $response->getBody()->getContents();
        if (empty($contentData)){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND,"商品错误");
        }
        $result = json_decode($contentData,true);

        if(empty($result["data"]) || empty($result["data"]["spu"])){
            return null;
        }

        return $result["data"]["spu"];
    }

    public function packProductDetail($id,$productId,$name,$imgUrl,$price,$inUrl,$outUrl){
        $oneGoods=[
            "id"=>$id,
            "productId"=>(string)$productId,
            "title"=>$name,
            "imagePath"=>$imgUrl,
            "price"=>(string)$price,
        ];
        return $oneGoods;
    }

    public function getProductQrCode($path){
        $pathNew = str_replace("plugin-private://","__plugin__/",$path);
        list($result,$wxApp) = $this->getWxApp();
        if ($result !== 0){
            DzqLog::error('WxShopTrait::getProductQrCode', [], $wxApp);
            return ["", false];
        }

        $qrResponse = $wxApp->app_code->get($pathNew);
        if(is_array($qrResponse) && isset($qrResponse['errcode']) && isset($qrResponse['errmsg'])) {
            DzqLog::error('WxShopTrait::getProductQrCode', [], $qrResponse['errmsg']);
            return ["", false];
        }
        $pStartIndex = strpos($path,"productId=");
        $productIdStr = substr($path, $pStartIndex+strlen("productId="));

        $fileName = "wxshop_".$productIdStr."_".time().".jpg";
        $qrBuf = $qrResponse->getBody()->getContents();
        /** @var ShopFileSave $shopFileSave */
        $shopFileSave = app("app")->make(ShopFileSave::class);

        return $shopFileSave->saveFile($fileName,$qrBuf);
    }

    public function getQRUrl($isRemote, $path){
        /** @var ShopFileSave $shopFileSave */
        $shopFileSave = app("app")->make(ShopFileSave::class);
        return $shopFileSave->getFilePath($isRemote, $path);
    }
}
