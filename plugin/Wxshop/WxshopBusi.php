<?php


namespace Plugin\Wxshop;


use App\Modules\ThreadTom\TomBaseBusi;
use Plugin\Wxshop\Controller\WxshopTrait;
use Plugin\Wxshop\Model\ShopProducts;

class WxshopBusi extends TomBaseBusi
{
    use WxshopTrait;

    public function create()
    {
        $productIds = $this->getParams('productIds');
        return $this->jsonReturn(['productIds' => $productIds]);
    }

    public function update()
    {
        $productIds = $this->getParams('productIds');
        return $this->jsonReturn(['productIds' => $productIds]);
    }

    public function select()
    {
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
}
