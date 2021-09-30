<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Discuz\Base\DzqPluginMigration;

class CreatePluginWxshopAccessTokens extends DzqPluginMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->create('plugin_wxshop_access_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true)->comment('自增id');
            $table->string('app_id', 64)->nullable(false)->comment('商店appid');
            $table->string('access_token',256)->nullable(false)->comment('访问token');
            $table->unsignedBigInteger('expires_in')->nullable(false)->comment('过期时间');
            $table->timestamp('created_at')->nullable(false)->default(new Expression('CURRENT_TIMESTAMP'))->comment('创建时间');
            $table->timestamp('updated_at')->nullable(false)->default(new Expression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('更新时间');
            $table->unique(['app_id'],'index_app_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->dropIfExists('plugin_wxshop_access_tokens');
    }
}
