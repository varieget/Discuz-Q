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
use Illuminate\Support\Str;

class ThreadText extends DzqModel
{
    protected $table = 'thread_text';
    const SUMMARY_LENGTH = 80;

    /**
     * 摘要结尾
     */
    const SUMMARY_END_WITH = '...';

    public function saveText()
    {

    }

    public function getSummary($text)
    {
        $content = strip_tags($text);
        if (mb_strlen($content) > self::SUMMARY_LENGTH) {
            $content = Str::substr($content, 0, self::SUMMARY_LENGTH) . self::SUMMARY_END_WITH;
        }
        return $content;
    }
}
