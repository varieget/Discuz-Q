<?php

/**
 * Copyright (C) 2020 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Discuz\Database\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;

class CreateThreadFissionReads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->create('thread_fission_reads', function (Blueprint $table) {
            $table->id()->comment('id');
            $table->unsignedBigInteger('thread_id')->nullable(false)->default(0)->comment('主题id');
            $table->unsignedBigInteger('user_id')->nullable(false)->default(0)->comment('分享者id');
            $table->unsignedBigInteger('to_user_id')->nullable(false)->default(0)->comment('阅读者id');
            $table->tinyInteger('is_new')->unsigned()->nullable(false)->default(0)->comment('是否为拉新 0：否、1：是');
            $table->tinyInteger('from')->unsigned()->nullable(false)->default(0)->comment('分享来源 1：站内阅读、2：海报阅读、3：链接阅读');
            $table->tinyInteger('is_fissioned')->unsigned()->nullable(false)->default(0)->comment('是否已分享处理 0：否、1：是');
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
        $this->schema()->dropIfExists('thread_fission_reads');
    }
}
