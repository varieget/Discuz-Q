<?php


namespace Plugin\Shop;


use App\Modules\ThreadTom\TomBaseBusi;
use App\Modules\ThreadTom\TomConfig;
use Plugin\Shop\Controller\WxShopTrait;
use Plugin\Shop\Model\ShopProducts;

class ShopBusi extends TomBaseBusi
{
    use WxShopTrait;

    public function create()
    {
        if ($this->tomId == TomConfig::TOM_GOODS){
            return $this->jsonReturn($this->body);
        }else{
            $productIds = $this->getParams('productIds');
            $productData = $this->doProduct($productIds);
            return $this->jsonReturn(["products"=>$productData]);
        }

    }

    public function update()
    {
        if ($this->tomId == TomConfig::TOM_GOODS){
            return $this->jsonReturn($this->body);
        }else {
            $productIds = $this->getParams('productIds');
            $productData = $this->doProduct($productIds);
            return $this->jsonReturn(["products"=>$productData]);
        }
    }

    public function select()
    {
        if ($this->tomId == TomConfig::TOM_GOODS) {
            return $this->jsonReturn($this->body);
        }else{
            return $this->selectWxShop();
        }
    }
    private function selectWxShop(){
        $qrCode = "";
        $setting = $this->getSetting();
        if ($setting && isset($setting["wx_qrcode"])){
            $qrCode = $setting["wx_qrcode"];
        }

        $products = $this->getParams("products");

        /** @var ShopProducts $product */
        foreach ($products as &$product){
            if (!empty($product->detail_qrcode)){
                $product->detail_qrcode = $this->getQRUrl($product->is_remote,$product->detail_qrcode);
            }else{
                $product->detail_qrcode = $qrCode;
            }
        }

        return $this->jsonReturn(["products"=>$products]);
    }

    private function selectWxShop2(){
        $config = $this->getConfig();
        $appId = $config["app_id"];

        $qrCode = "";
        $setting = $this->getSetting();
        if ($setting && isset($setting["wx_qrcode"])){
            $qrCode = $setting["wx_qrcode"];
        }

        $result = [];
        $productIds = $this->getParams('productIds');
        $productDataList = ShopProducts::query()->where("app_id",$appId)->whereIn("id",$productIds)->get();


        /** @var ShopProducts $product */
        foreach ($productDataList as $product){
            $outUrl = $qrCode;
            if (!empty($product->out_url)){
                $outUrl = $product->out_url;
            }
            $oneProduct = $this->packProductDetail($product->id,$product->product_id,$product->name,
                $product->img_url,$product->price,$product->in_url, $outUrl);
            $result[]=$oneProduct;
        }
        return $this->jsonReturn($result);
    }


    private function doProduct($productIdList){
        if (empty($productIdList)){
           return;
        }
        $config = $this->getSetting();
        $wxAppId = $config["wx_app_id"];
        list($result,$accssToken) = $this->getAccessToken();
        if ($result !== 0){
            return;
        }

        $result = [];
        foreach ($productIdList as $productId){
            $productId =  (string)$productId;
            $productInfo = $this->getProductInfo($accssToken, $productId);
            if (empty($productInfo)){
                continue;
            }

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

                $result[] = $oneShopProduct;
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

                $result[] = $productOld;
            }
        }

        return $result;
    }

}
