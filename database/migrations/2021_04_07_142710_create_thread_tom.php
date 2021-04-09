<?php

use Discuz\Database\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
/*
 * create table thread_tom
(
    `id`         bigint(20) unsigned not null auto_increment comment '自增id',
    `thread_id`  int        not null comment '帖子id',
    `tom_type`       varchar(10)         not null comment '资源类型：image/media/question/vote',
    `key`        varchar(10)         not null comment '元数据索引$0,$1,$2 资源占位符',
    `value`      text comment '资源结构化数据',
    `is_label`        tinyint comment '是否标记标签属性',
    `created_at` timestamp           not null default current_timestamp comment '创建时间',
    `updated_at` timestamp           not null default current_timestamp on update current_timestamp comment '更新时间',
    primary key (`id`),
    key `thread_id_key` (`thread_id`,`tom_type`,`key`,`status`),
    key `type`(`type`)
) engine = InnoDB
default charset = utf8mb4 comment '帖子资源数据';


 * */
class CreateThreadTom extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->create('thread_tom', function (Blueprint $table) {
            $table->id()->comment('自增id');
            $table->unsignedBigInteger('thread_id')->nullable(false)->comment('帖子id');
            $table->string('tom_type', 20)->nullable(false)->default('')->comment('资源类型：image/media/question/vote....');
            $table->string('key', 10)->nullable(false)->default('')->comment('元数据索引 $0,$1,$2 资源占位符');
            $table->text('value')->nullable()->comment('资源结构化数据');
            $table->tinyInteger('is_label')->nullable(false)->default(0)->comment('是否标记标签属性；预留字段');
            $table->timestamp('created_at')->nullable(false)->default(new Expression('CURRENT_TIMESTAMP'))->comment('创建时间');
            $table->timestamp('updated_at')->nullable(false)->default(new Expression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('更新时间');
            $table->index(['thread_id','tom_type','key','status'], 'thread_id');
            $table->index('type', 'type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->dropIfExists('thread_tom');
    }
}
