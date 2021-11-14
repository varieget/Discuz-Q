<?php

use Discuz\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddUserIdToOrderChildren extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->table('order_children', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->after('thread_id')->comment('支付人id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->table('order_children', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
        });
    }
}
