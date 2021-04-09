<?php

use Discuz\Database\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
/*
 * create table thread_hot
(
    `id`           bigint(20) unsigned    not null auto_increment comment '自增id',
    `thread_id`     int            not null comment '帖子id',
    `comment_count` int unsigned default 0 not null comment '评论数',
    `view_count`    int unsigned default 0 not null comment '查看数',
    `reward_count`  int unsigned default 0 not null comment '打赏数',
    `pay_count`     int unsigned default 0 not null comment '付费数',
    `last_post_time` timestamp comment '最后一条评论发布的时间',
    `last_post_user` bigint(32) comment '最后一条评论发布的用户id',
    `created_at`   timestamp              not null default current_timestamp comment '创建时间',
    `updated_at`   timestamp              not null default current_timestamp on update current_timestamp comment '更新时间',
    primary key (`id`),
    unique key `thread_id` (`thread_id`),
    key `last_post_time`(`last_post_time`)
) engine = InnoDB
default charset = utf8mb4 comment '帖子热点数据';

 * */
class CreateThreadHot extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->create('thread_hot', function (Blueprint $table) {
            $table->id()->comment('自增id');
            $table->unsignedBigInteger('thread_id')->nullable(false)->comment('帖子id');
            $table->unsignedInteger('comment_count')->nullable(false)->default(0)->comment('评论数');
            $table->unsignedInteger('view_count')->nullable(false)->default(0)->comment('查看数');
            $table->unsignedInteger('reward_count')->nullable(false)->default(0)->comment('打赏数');
            $table->unsignedInteger('pay_count')->nullable(false)->default(0)->comment('付费数');
            $table->timestamp('last_post_time')->nullable()->comment('最后一条评论发布的时间');
            $table->unsignedBigInteger('last_post_user')->nullable(false)->default(0)->comment('最后一条评论发布的用户id');
            $table->timestamp('created_at')->nullable(false)->default(new Expression('CURRENT_TIMESTAMP'))->comment('创建时间');
            $table->timestamp('updated_at')->nullable(false)->default(new Expression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('更新时间');
            $table->unique('thread_id', 'thread_id');
            $table->index('last_post_time', 'last_post_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->dropIfExists('thread_hot');
    }
}
