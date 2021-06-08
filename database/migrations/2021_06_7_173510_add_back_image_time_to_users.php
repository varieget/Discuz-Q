<?php

use Discuz\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddBackImageTimeToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->table('users', function (Blueprint $table) {
            $table->dateTime('background_at')->nullable()->after('avatar_at')->comment('修改背景图时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->table('users', function (Blueprint $table) {
            $table->dropColumn('background_at');
        });
    }
}
