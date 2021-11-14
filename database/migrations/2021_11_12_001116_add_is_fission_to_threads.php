<?php

use Discuz\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddIsFissionToThreads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->table('threads', function (Blueprint $table) {
            $table->tinyInteger('is_fission')->default(0)->comment('是否为分享裂变；0：否、1：是');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->table('threads', function (Blueprint $table) {
            $table->tinyInteger('is_fission');
        });
    }
}
