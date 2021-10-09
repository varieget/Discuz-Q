<?php


namespace Plugin\Wxshop\Model;

use Carbon\Carbon;
use Discuz\Base\DzqModel;


/**
 * @property int $id
 * @property string $app_id
 * @property string $product_id
 * @property string $name
 * @property string $img_url
 * @property string $price
 * @property string $in_url
 * @property string $out_url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ShopProducts  extends DzqModel
{
    protected $table = "plugin_wxshop_shop_products";
}
