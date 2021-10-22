<?php


namespace Plugin\Shop;


use App\Modules\ThreadTom\TomBaseBusi;
use Discuz\Base\DzqLog;
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
            if ($item["type"] == self::TYPE_ORIGIN){

            }else if ($item["type"] == self::TYPE_WX_SHOP){
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
            if ($item["type"] == self::TYPE_ORIGIN){

            }else if ($item["type"] == self::TYPE_WX_SHOP){
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
        foreach ($products as &$item){
            if(!isset($item["type"])){
                continue;
            }
            if ($item["type"] == self::TYPE_ORIGIN){

            }else if ($item["type"] == self::TYPE_WX_SHOP){
                if (!isset($item["data"])){
                    continue;
                }
                $item["data"] = $this->selectWxShop($item["data"]);
            }
        }
        $productData["products"] = $products;
        return $this->jsonReturn($productData);
    }


    private function selectWxShop( &$product){
        $qrCode = "";
        $setting = $this->getSetting();
        if ($setting && isset($setting["wxQrcode"]) && isset($setting["wxQrcode"])){
            $qrCode = $setting["wxQrcode"];
        }

        if (!empty($product["detailQrcode"])){
            $product["detailQrcode"] = $this->getQRUrl($product["isRemote"],$product["detailQrcode"]);
        }else{
            $product["detailQrcode"] = $qrCode;
        }

        return $product;
    }

    private function doProduct($productId){
        $resultData = false;

        DzqLog::error("aaa",[],"100001");

        $config = $this->getSetting();
        $wxAppId = $config["wxAppId"];
        DzqLog::error("aaa",[],"100002");
        list($result,$accssToken) = $this->getAccessToken();
        DzqLog::error("aaa",[],"100003");
        if ($result !== 0){
            DzqLog::error("aaa",[],"100004");
            return $resultData;
        }
        DzqLog::error("aaa",[],"100005");

        $productId =  (string)$productId;
        $productInfo = $this->getProductInfo($accssToken, $productId);
        DzqLog::error("aaa",[],"100006");
        if (empty($productInfo)){
            DzqLog::error("aaa",[],"100007");
            return $resultData;
        }
        DzqLog::error("aaa",[],"100008");
        $imgUrl = "";
        if (count($productInfo["head_img"])>0){
            $imgUrl=$productInfo["head_img"][0];
        }
        $name = $productInfo["title"];
        $price = $productInfo["min_price"]/100;
        $productIdTemp = $productInfo["product_id"];
        $path = $productInfo["path"]; //微信内部url, plugin-private:

        /** @var ShopProducts $productOld */
        $productOld = ShopProducts::query()->where("app_id",$wxAppId)
            ->where("product_id",$productId)->first();
        if (empty($productOld)){
            //拉取二维码
            list($qrPath,$isRemote) = $this->getProductQrCode($path);

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
            $oneShopProduct->save();

            $resultData = $oneShopProduct;
        }else{
            $productOld->title = $name;
            $productOld->image_path = $imgUrl;
            $productOld->price = (string)$price;
            $productOld->path = $path;
            $productOld->detail_url = $path;
            if (empty($productOld->detail_qrcode)){
                list($qrPath,$isRemote) = $this->getProductQrCode($path);
                $productOld->detail_qrcode = $qrPath;
                $productOld->is_remote = $isRemote?1:0;
            }

            $productOld->save();

            $resultData = $productOld;
        }

        $resultDataTemp = $this->camelData($resultData);
        return $resultDataTemp;
    }

}
