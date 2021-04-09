<?php
/**
 * Copyright (C) 2021 Tencent Cloud.
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

namespace App\Models;

use Discuz\Base\DzqModel;

/**
 * @property int $id
 * @property int $thread_id
 * @property int $comment_count
 * @property int $view_count
 * @property int $reward_count
 * @property int $pay_count
 * @property string $last_post_time
 * @property int $last_post_user
 * @property string $create_at
 * @property string $update_at
 */
class ThreadHot extends DzqModel
{
    protected $table = 'thread_hot';


    protected $dateFormat = 'U';


}
