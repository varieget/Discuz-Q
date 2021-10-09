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

namespace Plugin\Wxshop\Controller;


use App\Common\ResponseCode;
use App\Models\PluginSettings;
use Discuz\Wechat\EasyWechatTrait;
use GuzzleHttp\Client;

trait WxshopTrait
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
            $this->config = require(__DIR__."/../config.php");
        }
        return $this->config;
    }

    public function getAccessToken($wxshopAppId){
        $config = $this->getConfig();
        $settingData = PluginSettings::query()->where("app_id",$config["app_id"])->first();
        if (empty($settingData)){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND,"插件没配置");
        }
        if (empty($settingData->value)){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND,"插件没配置");
        }

        $valueJson = json_decode($settingData->value);
        if (!isset($valueJson->wx_app_id) || !isset($valueJson->wx_app_secret)){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND,"插件没配置");
        }

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$valueJson->wx_app_id."&secret=".$valueJson->wx_app_secret;
        $app = $this->miniProgram(["app_id"=>$valueJson->wx_app_id,"secret"=>$valueJson->wx_app_secret]);
        $accessToken = $app->access_token->getToken(false);
        if (empty($accessToken["access_token"])){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND,"插件配置错误");
        }

        return $accessToken["access_token"];
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
            "productId"=>$productId,
            "name"=>$name,
            "imgUrl"=>$imgUrl,
            "price"=>(string)$price,
            "inUrl"=>$inUrl, //微信内部url, plugin-private://
            "outUrl"=>$outUrl //二维码地址
        ];
        return $oneGoods;
    }
}
