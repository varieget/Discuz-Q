<?php

use Discuz\Database\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
/*
 * create table thread_text
(
    `id`          bigint(20) unsigned not null auto_increment comment '自增id',
    `user_id`      bigint(32)          not null comment '用户id',
    `category_id`  int(10)             not null comment '分类id',
    `title`       varchar(200)        not null comment '帖子标题',
    `summary`     text                not null comment '文档摘要信息',
    `text`        mediumtext comment '帖子正文',
    `cover`       text comment '封面图',
    `tags`        text comment '标签和关键词',
    `longitude`   decimal(10, 7)               default 0.0000000 not null comment '经度',
    `latitude`    decimal(10, 7)               default 0.0000000 not null comment '纬度',
    `address`     varchar(100)        not null comment '地址',
    `location`    varchar(100)        not null comment '位置',
    `is_sticky`    tinyint                      default 0 comment '0：不置顶 1:置顶',
    `is_essence`   tinyint                      default 0 comment '0：不加精 1：加精',
    `is_anonymous` tinyint                      default 0 comment '0：不匿名 1：匿名',
    `is_site`  tinyint                      default 0 comment '是否推荐到首页（0否 1是）',
    `status`      tinyint                      default 1 comment '-1:删除 0：待审核 1：正常 2：草稿 3：隐藏',
    `created_at`  timestamp           not null default current_timestamp comment '创建时间',
    `updated_at`  timestamp           not null default current_timestamp on update current_timestamp comment '更新时间',
    primary key (`id`),
    unique key `ut` ( `user_id`, `id`),
    key `create_at`(`create_at`)
) engine = InnoDB
default charset = utf8mb4 comment '帖子正文';

 * */
class CreateThreadText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->create('thread_text', function (Blueprint $table) {
            $table->id()->comment('自增id');
            $table->unsignedBigInteger('user_id')->nullable(false)->comment('用户id');
            $table->unsignedInteger('category_id')->nullable(false)->comment('分类id');
            $table->string('title', 200)->nullable(false)->default('')->comment('帖子标题');
            $table->text('summary')->nullable()->comment('文档摘要信息');
            $table->mediumText('text')->nullable()->comment('帖子正文');
            $table->text('cover')->nullable()->comment('封面图');
            $table->text('tags')->nullable()->comment('标签和关键词');
            $table->decimal('longitude', 10, 7)->nullable(false)->default(0.0000000)->comment('经度');
            $table->decimal('latitude', 10, 7)->nullable(false)->default(0.0000000)->comment('纬度');
            $table->string('address', 100)->nullable(false)->default('')->comment('地址');
            $table->string('location', 100)->nullable(false)->default('')->comment('位置');
            $table->tinyInteger('is_sticky')->nullable(false)->default(0)->comment('0：不置顶 1:置顶');
            $table->tinyInteger('is_essence')->nullable(false)->default(0)->comment('0：不加精 1：加精');
            $table->tinyInteger('is_anonymous')->nullable(false)->default(0)->comment('0：不匿名 1：匿名');
            $table->tinyInteger('is_site')->nullable(false)->default(0)->comment('是否推荐到首页（0否 1是）');
            $table->tinyInteger('status')->nullable(false)->default(1)->comment('-1:删除 0：待审核 1：正常 2：草稿 3：隐藏）');
            $table->timestamp('created_at')->nullable(false)->default(new Expression('CURRENT_TIMESTAMP'))->comment('创建时间');
            $table->timestamp('updated_at')->nullable(false)->default(new Expression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('更新时间');
            $table->unique(['user_id','id'], 'ut');
            $table->index('created_at', 'created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->dropIfExists('thread_text');
    }
}
