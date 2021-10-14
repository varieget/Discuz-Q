<?php


namespace Plugin\Shop\Controller;


use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Plugin\Wxshop\Model\ShopProducts;

class WxShopAddController extends DzqController
{
    use WxShopTrait;
    private $activity = null;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return $this->checkPermission($userRepo,true);
    }

    public function main()
    {
        $productIdList = $this->inPut('productIdList');
        if (empty($productIdList)){
            $this->outPut(ResponseCode::INVALID_PARAMETER,"参数错误");
        }
        $config = $this->getConfig();
        $appId = $config["app_id"];
        $accssToken = $this->getAccessToken(0);

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
            $inUrl = $productInfo["path"]; //微信内部url, plugin-private:
            $qrBuf = $this->getProductQrCode($inUrl);

            $productOld = ShopProducts::query()->where("app_id",$appId)
                ->where("product_id",$productId)->first();
            if (empty($productOld)){
                $oneShopProduct = new ShopProducts();
                $oneShopProduct->app_id = $appId;
                $oneShopProduct->platform_id = $productId;
                $oneShopProduct->title = $name;
                $oneShopProduct->image_path = $imgUrl;
                $oneShopProduct->price = (string)$price;
                $oneShopProduct->in_url = $inUrl;
                $oneShopProduct->out_url = "";
                $oneShopProduct->save();
            }else{
                $productOld->title = $name;
                $productOld->image_path = $imgUrl;
                $productOld->price = (string)$price;
                $productOld->in_url = $inUrl;
                $productOld->save();
            }
        }

        //下发
        $productDataList = ShopProducts::query()->where("app_id",$appId)
            ->whereIn("product_id",$productIdList)->get();
        $productList = [];
        $productDataList->each(function (ShopProducts $item) use (&$productList){
            $oneProduct = $this->packProductDetail($item->id,$item->product_id,$item->name,$item->img_url,
                $item->price,$item->in_url,$item->out_url);
            $productList[] = $oneProduct;
        });

        $resultData = [];
        $resultData["productList"]=$productList;
        $this->output(0,"",$resultData);
    }
}
