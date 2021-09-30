<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Discuz\Base\DzqPluginMigration;

class CreatePluginWxshopShopGoods extends DzqPluginMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->create('plugin_wxshop_shop_goods', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true)->comment('自增id');
            $table->string('app_id', 64)->nullable(false)->comment('商店appid');
            $table->string('product_id',10)->nullable(false)->comment('商品id');
            $table->string('name', 128)->nullable(false)->comment('商品名');
            $table->string('price',10)->nullable(false)->comment('价格');
            $table->string('in_url',128)->nullable(false)->comment('微信url，小程序，h5直接跳');
            $table->string('out_url',128)->nullable(false)->comment('外部url，扫码跳');
            $table->timestamp('created_at')->nullable(false)->default(new Expression('CURRENT_TIMESTAMP'))->comment('创建时间');
            $table->timestamp('updated_at')->nullable(false)->default(new Expression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('更新时间');

            $table->unique(['app_id','product_id'],'index_app_product_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->dropIfExists('plugin_wxshop_shop_goods');
    }
}
