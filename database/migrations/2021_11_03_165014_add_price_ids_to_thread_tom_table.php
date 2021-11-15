<?php

use Discuz\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddPriceIdsToThreadTomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->table('thread_tom', function (Blueprint $table) {
            $table->string('price_ids')->default('{}')->comment('插件/组件部分付费id集合');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->table('thread_tom', function (Blueprint $table) {
            $table->dropColumn('price_ids');
        });
    }
}
