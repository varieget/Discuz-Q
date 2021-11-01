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

namespace App\Api\Controller\Crawler;

use App\Common\ResponseCode;
use Discuz\Base\DzqAdminController;

class CheckCrawlerProcessController extends DzqAdminController
{
    use CrawlerTrait;

    public function main()
    {
        $publicPath = public_path();
        $lockPath = $publicPath . DIRECTORY_SEPARATOR . 'crawlerSplQueueLock.conf';
        $lockFileContent = $this->getLockFileContent($lockPath);
        $this->outPut(ResponseCode::SUCCESS, '', $lockFileContent);
    }
}
