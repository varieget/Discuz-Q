<?php


namespace Plugin\Shop;

use App\Api\Serializer\AttachmentSerializer;
use App\Common\CacheKey;
use App\Models\Attachment;
use App\Models\PluginSettings;
use App\Modules\ThreadTom\TomBaseBusi;
use Discuz\Base\DzqCache;
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

        $this->selectWxshop($products);

        $productData["products"] = $products;
        return $this->jsonReturn($productData);
    }


    private function selectWxShop( &$products){
        $attachmentIds = [];
        foreach ($products as $item){
            if (isset($item["type"]) && self::TYPE_WX_SHOP == $item["type"]){
                if (!isset($item["data"]) || !isset($item["data"]["detailQrcode"])){
                    continue;
                }
                $attachmentIds[] = $item["data"]["detailQrcode"];
            }
        }

        $attachments = DzqCache::hMGet(CacheKey::LIST_THREADS_V3_ATTACHMENT, $attachmentIds, function ($attachmentIds) {
            return Attachment::query()->whereIn('id', $attachmentIds)->get()->toArray();
        }, 'id');

        $attachmentIdUrl = [];
        $serializer = $this->app->make(AttachmentSerializer::class);
        foreach ($attachments as $attachment) {
            $item = $this->camelData($serializer->getBeautyAttachment($attachment));
            $attachmentIdUrl[(string)$item["id"]] = $item['url'];
        }

        $qrCode = "";
        $setting = app()->make(PluginSettings::class)->getSetting($this->tomId);
        if ($setting && isset($setting["wxQrcode"]) && isset($setting["wxQrcode"])){
            $qrCode = $setting["wxQrcode"];
        }

        foreach ($products as &$item){
            if (isset($item["type"]) && self::TYPE_WX_SHOP == $item["type"]){
                if (!isset($item["data"]) || !isset($item["data"]["detailQrcode"])){
                    continue;
                }
                if(isset($attachmentIdUrl[$item["data"]["detailQrcode"]])){
                    $item["data"]["detailQrcode"] = $attachmentIdUrl[$item["data"]["detailQrcode"]];
                }else{
                    $item["data"]["detailQrcode"] = $qrCode;
                }
            }
        }
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
            $attachId = $this->getProductQrCode($this->tomId,$path);

            $oneShopProduct = new ShopProducts();
            $oneShopProduct->app_id = $wxAppId;
            $oneShopProduct->product_id = $productId;
            $oneShopProduct->title = $name;
            $oneShopProduct->image_path = $imgUrl;
            $oneShopProduct->price = (string)$price;
            $oneShopProduct->path = $path;
            $oneShopProduct->detail_url = $path;
            $oneShopProduct->detail_qrcode = (string)$attachId;
            $oneShopProduct->is_remote = 0;
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
                $attachId = $this->getProductQrCode($this->tomId,$path);
                $productOld->detail_qrcode = (string)$attachId;
                $productOld->is_remote = 0;
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

        return $resultDataTemp;
    }
}
