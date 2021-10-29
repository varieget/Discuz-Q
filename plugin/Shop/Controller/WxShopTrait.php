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
use App\Common\Utils;
use App\Models\PluginSettings;
use Discuz\Base\DzqLog;
use Discuz\Wechat\EasyWechatTrait;
use GuzzleHttp\Client;

trait WxShopTrait
{
    use EasyWechatTrait;

    protected $config;
    protected $httpClient;
    protected $wxApp;
    protected $accessToken;
    protected $settingData;

    private function getWxShopHttpClient(){
        if (empty($this->httpClient)){
            $this->httpClient = new Client([]);
        }
        return $this->httpClient;
    }
    /**
     * @return
     */
    public function getSetting(){
        if (!empty($this->settingData)){
            return $this->settingData;
        }

        $appid = Utils::getAppKey("plugin_appid");
        if (empty($appid)){
            return false;
        }
        $settingData = app()->make(PluginSettings::class)->getSetting($appid);
        if (empty($settingData)){
           return false;
        }
        $this->settingData = $settingData;
        return $this->settingData;
    }

    public function getWxApp(){
        if (!empty($this->wxApp)){
            return [0,$this->wxApp];
        }
        $settingData = $this->getSetting();
        if (empty($settingData)){
            return [ResponseCode::RESOURCE_NOT_FOUND,"插件没配置"];
        }
        if (!isset($settingData["wxAppId"])
            || !isset($settingData["wxAppSecret"])){
            return [ResponseCode::RESOURCE_NOT_FOUND,"插件没配置"];
        }
        $this->wxApp = $this->miniProgram(["app_id"=>$settingData["wxAppId"],"secret"=>$settingData["wxAppSecret"]]);
        return [0, $this->wxApp];
    }

    public function getAccessToken(){
        if (!empty($this->accessToken)){
            return [0,$this->accessToken];
        }

        list($result,$wxApp) = $this->getWxApp();
        if ($result !== 0){
            return [$result,$wxApp];
        }
        $accessToken = $wxApp->access_token->getToken(true);
        if (empty($accessToken["access_token"])){
            return [ResponseCode::RESOURCE_NOT_FOUND,"插件配置错误"];
        }
        $this->accessToken = $accessToken["access_token"];
        return [0,$this->accessToken];
    }


    private function getShopList($accessToken,$page,$perPage)
    {
        $url = "https://api.weixin.qq.com/product/spu/get_list?access_token=".$accessToken;
        $one = $this->getWxShopHttpClient();

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

        $httpClientTemp = $this->getWxShopHttpClient();

        $body = [ "product_id"=>$productId,
            "out_product_id"=>"",
            "need_edit_spu"=>0
        ];
        $bodyStr = json_encode($body);

        $options = [
            "body"=>$bodyStr
        ];

        $response = $httpClientTemp->request("post", $url, $options);

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

    public function getSchemeProduct($path)
    {
        list($ret2,$accessToken) = $this->getAccessToken();
        $settingData = $this->getSetting();
        if (empty($settingData["wxScheme"])){
            return "";
        }

        $wxAppId = $settingData["wxAppId"];
        $wxAppSecret = $settingData["wxAppSecret"];

        $pathNew = str_replace("plugin-private://","__plugin__/",$path);
        $post_data['jump_wxa']['path'] = $pathNew;
        $post_data['jump_wxa']['query'] = '2';
        $postBody = json_encode($post_data);

        return $this->getScheme($wxAppId,$wxAppSecret,$accessToken,$postBody);
    }

    public function getScheme($appid,$secret,$accessToken,string $body)
    {
        $httpClientTemp = $this->getWxShopHttpClient();
        if (empty($accessToken)){
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret;

            $response = $httpClientTemp->request("get", $url, []);
            $statusCode = $response->getStatusCode();
            if ($statusCode != 200){
                return "";
            }
            $contentData = $response->getBody()->getContents();
            if (empty($contentData)){
                return "";
            }
            $result = json_decode($contentData,true);
            $accessToken = $result['access_token'];
        }

        $options = [];
        if (!empty($body)){
            $options["body"] = $body;
        }
        $post_url = 'https://api.weixin.qq.com/wxa/generatescheme?access_token='.$accessToken;
        $response = $httpClientTemp->request("post", $post_url, $options);
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200){
            return "";
        }
        $contentData = $response->getBody()->getContents();
        if (empty($contentData)){
            return "";
        }
        $result = json_decode($contentData,true);
        return isset($result['openlink']) ? $result['openlink'] : "";
    }
}
