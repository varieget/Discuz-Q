<?php


namespace Plugin\Shop;


use App\Common\CacheKey;
use App\Common\Utils;
use App\Models\PluginSettings;
use App\Modules\ThreadTom\TomBaseBusi;
use Discuz\Base\DzqCache;
use Illuminate\Database\Eloquent\Model;
use Plugin\Shop\Controller\WxShopTrait;
use Plugin\Shop\Model\ShopProducts;

class ShopBusi extends TomBaseBusi
{
    use WxShopTrait;

    public const TYPE_ORIGIN = 10;
    public const TYPE_WX_SHOP = 11;

    public function setSetting($privateValue,$publicValue)
    {
        Utils::setAppKey("plugin_appid",$this->tomId);

        //判断isOpen变化了，则清帖子缓存
        $settingNew = array_merge($privateValue,$publicValue);
        $settingOld = $this->getSetting();

        if ($settingOld["isOpen"] == $settingNew["isOpen"]){
            return;
        }

        DzqCache::delKey(CacheKey::LIST_THREADS_V3_CREATE_TIME);
        DzqCache::delKey(CacheKey::LIST_THREADS_V3_ATTENTION);
        DzqCache::delKey(CacheKey::LIST_THREADS_V3_VIEW_COUNT);
        DzqCache::delKey(CacheKey::LIST_THREADS_V3_POST_TIME);
        DzqCache::delKey(CacheKey::LIST_THREADS_V3_COMPLEX);
        DzqCache::delKey(CacheKey::LIST_THREADS_V3_SEQUENCE);
        DzqCache::delKey(CacheKey::LIST_THREADS_V3_SEARCH);
        DzqCache::delKey(CacheKey::LIST_THREADS_V3_PAID_HOMEPAGE);
    }

    public function create()
    {
        Utils::setAppKey("plugin_appid",$this->tomId);

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
        Utils::setAppKey("plugin_appid",$this->tomId);

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
        Utils::setAppKey("plugin_appid",$this->tomId);
        $setting = $this->getSetting();
        if (!isset($setting["isOpen"]) || $setting["isOpen"] == 0){
            return;
        }

        $products = $this->getParams('products');
        foreach ($products as &$item){
            if(!isset($item["type"])){
                continue;
            }
            if (self::TYPE_WX_SHOP == $item["type"]){
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
            $product["detailQrcode"] = $this->getQRUrl($product["isRemote"]??0,$product["detailQrcode"]);
        }else{
            $product["detailQrcode"] = $qrCode;
        }

        return $product;
    }

    private function doProduct($productId){
        $resultData = false;

        $config = app()->make(PluginSettings::class)->getSetting($this->tomId);
        $wxAppId = $config["wxAppId"];

        list($result,$accssToken) = $this->getAccessToken();
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
            $oneShopProduct->detail_scheme = $this->getSchemeProduct($path);

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

                $productOld->detail_scheme = $this->getSchemeProduct($path);
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
