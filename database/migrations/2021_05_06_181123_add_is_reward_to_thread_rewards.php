<?php

use Discuz\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddIsRewardToThreadRewards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->table('thread_rewards', function (Blueprint $table) {
            $table->tinyInteger('is_reward')->unsigned()->default(0)->after('remain_money')->comment('是否打赏 0否 1是');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->table('thread_rewards', function (Blueprint $table) {
            $table->dropColumn('is_reward');
        });
    }
}
