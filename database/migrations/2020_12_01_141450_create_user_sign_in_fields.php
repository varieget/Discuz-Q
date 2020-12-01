<?php

use Discuz\Database\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
/*
 * create table `user_sign_in_fields`
(
    `id`         bigint(20) unsigned not null auto_increment comment '自增id',
    `user_id`    bigint(20) unsigned comment '用户user_id',
    `name`       varchar(20)         not null comment '用户端显示的字段名称',
    `type`       tinyint(4)          not null default 0 comment '0:单行文本框 1:多行文本框 2:单选 3:复选 4:图片上传 5:附件上传',
    `fields_ext` text comment '字段扩展信息，Json表示选项内容',
    `created_at` timestamp           DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` timestamp           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    primary key (`id`),
    key `user_id` (`user_id`)
) engine = InnoDB default charset = utf8mb4 comment '用户登录必填信息详情表';
 * */
class CreateUserSignInFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->create('user_sign_in_fields', function (Blueprint $table) {
            $table->unsignedBigInteger('id',true)->comment('自增id');
            $table->unsignedBigInteger('user_id')->index()->comment('用户user_id');
            $table->string('name', 20)->nullable(false)->comment('用户端显示的字段名称');
            $table->tinyInteger('type')->nullable(false)->default(0)->comment('0:单行文本框 1:多行文本框 2:单选 3:复选 4:图片上传 5:附件上传');
            $table->text('fields_ext')->comment('字段扩展信息，Json表示选项内容');
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
        $this->schema()->dropIfExists('user_sign_in_fields');
    }
}
