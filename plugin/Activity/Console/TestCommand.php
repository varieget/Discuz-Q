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

namespace Plugin\Activity\Console;


use Discuz\Base\DzqCommand;

class TestCommand extends DzqCommand
{

    protected $signature = 'activity:test';
    protected $description = '执行一个脚本命令,控制台执行[php disco activity:test]';
    protected function main()
    {
        $this->info('Hello Discuz! Q Plugin Activity');
    }
}
