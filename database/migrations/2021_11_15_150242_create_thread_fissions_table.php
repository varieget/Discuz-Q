<?php

use Discuz\Database\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;

class CreateFissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->create('thread_fissions', function (Blueprint $table) {
            $table->id()->comment('id');
            $table->bigInteger('thread_id')->nullable(false)->default(0)->comment('主题id');
            $table->tinyInteger('read_count')->nullable(false)->default(0)->comment('阅读帖子人数');
            $table->tinyInteger('divide_limit')->nullable(false)->default(0)->comment('瓜分次数限制');
            $table->tinyInteger('type')->nullable(false)->default(0)->comment('发放规则；1：随机、2：定额');
            $table->decimal('min_money',10 ,2)->default(0.00)->comment('瓜分最低金额');
            $table->decimal('max_money',10 ,2)->default(0.00)->comment('瓜分最高金额');
            $table->unsignedInteger('new_user_scale')->default(0)->comment('新用户瓜分');
            $table->unsignedInteger('old_user_scale')->default(0)->comment('老用户瓜分');
            $table->tinyInteger('is_defend')->default(0)->comment('是否防作弊；0：否、1：是');
            $table->unsignedInteger('redpacket_number')->default(0)->comment('定额红包个数');
            $table->decimal('total_money',10 , 2)->default(0.00)->comment('帖子裂变总金额');
            $table->decimal('expend_money',10 , 2)->default(0.00)->comment('帖子裂变已发放金额');
            $table->unsignedInteger('expend_number')->default(0)->comment('已发放红包数');
            $table->dateTime('expired_at')->default(new Expression('CURRENT_TIMESTAMP'))->comment('到期时间');
            $table->timestamp('created_at')->default(new Expression('CURRENT_TIMESTAMP'))->comment('创建时间');
            $table->timestamp('updated_at')->default(new Expression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('更新时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->dropIfExists('thread_fissions');
    }
}
