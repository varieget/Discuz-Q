<?php


namespace Plugin\Shop;

use App\Common\Utils;
use App\Models\PluginSettings;
use App\Modules\ThreadTom\TomBaseBusi;
use Plugin\Shop\Controller\WxShopTrait;
use Plugin\Shop\Model\ShopProducts;

class ShopBusi extends TomBaseBusi
{
    use WxShopTrait;

    public const TYPE_ORIGIN = 10;
    public const TYPE_WX_SHOP = 11;

    public function create()
    {
        $products = $this->getParams('products');
        $productsNew = [];
        foreach ($products as $item){
            if(!isset($item["type"])){
                continue;
            }
            if (self::TYPE_WX_SHOP == $item["type"]){
                if (!isset($item["data"]) || !isset($item["data"]["productId"])){
                    continue;
                }
                $pData  = $this->doProduct($item["data"]["productId"]);
                if($pData !== false){
                    $item["data"] = $pData;
                }
            }
            $productsNew[] = $item;
            if (count($productsNew)>=10){
                break;
            }
        }
        $productData["products"] = $productsNew;
        return $this->jsonReturn($productData);
    }

    public function update()
    {
        $products = $this->getParams('products');
        $productsNew = [];
        foreach ($products as $item){
            if(!isset($item["type"])){
                continue;
            }
            if (self::TYPE_WX_SHOP == $item["type"]){
                if (!isset($item["data"]) || !isset($item["data"]["productId"])){
                    continue;
                }
                $pData  = $this->doProduct($item["data"]["productId"]);
                if($pData !== false){
                    $item["data"] = $pData;
                }
            }
            $productsNew[] = $item;
            if (count($productsNew)>=10){
                break;
            }
        }
        $productData["products"] = $productsNew;
        return $this->jsonReturn($productData);
    }

    public function select()
    {
        $products = $this->getParams('products');
        $productData["products"] = $products;
        return $this->jsonReturn($productData);
    }


    private function doProduct($productId){
        $resultData = false;

        $config = app()->make(PluginSettings::class)->getSetting($this->tomId);
        $wxAppId = $config["wxAppId"];

        list($result,$accssToken) = $this->getAccessToken($this->tomId);
        if ($result !== 0){
            return $resultData;
        }

        $productId =  (string)$productId;
        $productInfo = $this->getProductInfo($accssToken, $productId);
        if (empty($productInfo)){
            return $resultData;
        }
        $imgUrl = "";
        if (count($productInfo["head_img"])>0){
            $imgUrl=$productInfo["head_img"][0];
        }
        $name = $productInfo["title"];
        $price = $productInfo["min_price"]/100.0;
        $productIdTemp = $productInfo["product_id"];
        $path = $productInfo["path"]; //微信内部url, plugin-private:

        /** @var ShopProducts $productOld */
        $productOld = ShopProducts::query()->where("app_id",$wxAppId)
            ->where("product_id",$productId)->first();
        if (empty($productOld)){
            //拉取二维码
            list($qrPath,$isRemote) = $this->getProductQrCode($this->tomId,$path);

            $oneShopProduct = new ShopProducts();
            $oneShopProduct->app_id = $wxAppId;
            $oneShopProduct->product_id = $productId;
            $oneShopProduct->title = $name;
            $oneShopProduct->image_path = $imgUrl;
            $oneShopProduct->price = (string)$price;
            $oneShopProduct->path = $path;
            $oneShopProduct->detail_url = $path;
            $oneShopProduct->detail_qrcode = $qrPath;
            $oneShopProduct->is_remote = $isRemote?1:0;
            $oneShopProduct->detail_scheme = $this->getSchemeProduct($this->tomId,$path);

            $oneShopProduct->save();

            $resultData = $oneShopProduct;
        }else{
            $productOld->title = $name;
            $productOld->image_path = $imgUrl;
            $productOld->price = (string)$price;
            $productOld->path = $path;
            $productOld->detail_url = $path;
            if (empty($productOld->detail_qrcode)){
                list($qrPath,$isRemote) = $this->getProductQrCode($this->tomId,$path);
                $productOld->detail_qrcode = $qrPath;
                $productOld->is_remote = $isRemote?1:0;

                $productOld->detail_scheme = $this->getSchemeProduct($this->tomId,$path);
            }

            $productOld->save();

            $resultData = $productOld;
        }

        $resultDataTemp = [];
        $resultDataTemp["id"] =  $resultData["id"];
        $resultDataTemp["appId"] =  $resultData["app_id"];
        $resultDataTemp["productId"] =  $resultData["product_id"];
        $resultDataTemp["title"] =  $resultData["title"];
        $resultDataTemp["imagePath"] =  $resultData["image_path"];
        $resultDataTemp["price"] =  $resultData["price"];
        $resultDataTemp["path"] =  $resultData["path"];
        $resultDataTemp["detailUrl"] =  $resultData["detail_url"];
        $resultDataTemp["detailQrcode"] =  $resultData["detail_qrcode"];
        $resultDataTemp["detailScheme"] =  $resultData["detail_scheme"];
        $resultDataTemp["isRemote"] =  $resultData["is_remote"];

        return $resultDataTemp;
    }
}
