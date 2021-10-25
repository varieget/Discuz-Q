<?php

use Discuz\Base\DzqPluginMigration;
use Illuminate\Database\Schema\Blueprint;

class AddAdditionalInfoToActivity extends DzqPluginMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->table('plugin_activity_thread_activity', function (Blueprint $table) {
            $table->unsignedInteger('additional_info_type')->default(0)->comment('报名必填信息，类型累加；1：姓名、2：手机号、4：微信号、8：地址');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->table('plugin_activity_thread_activity', function (Blueprint $table) {
            $table->dropColumn('additional_info_type');
        });
    }
}
