<?php

use Discuz\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddFieldsToPluginShopWxshopProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->table('plugin_shop_wxshop_products', function (Blueprint $table) {
            //
            $table->text("attach_file_name")->comment("附件文件名")->after("is_remote");
            $table->text("attach_file_path")->comment("附件全路径")->after("is_remote");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->table('plugin_shop_wxshop_products', function (Blueprint $table) {
            //
            $table->dropColumn("attach_file_path");
            $table->dropColumn("attach_file_name");
        });
    }
}
