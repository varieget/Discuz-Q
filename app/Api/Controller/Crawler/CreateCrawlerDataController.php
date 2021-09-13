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

namespace App\Api\Controller\Crawler;

use App\Common\CacheKey;
use App\Common\ResponseCode;
use App\Models\Category;
use App\Models\Thread;
use App\Repositories\UserRepository;
use Discuz\Base\DzqAdminController;

class CreateCrawlerDataController extends DzqAdminController
{
    use CrawlerTrait;

    private $crawlerPlatform;

    private $categoryId;

    public function main()
    {
        $topic = $this->input('topic');
        $this->crawlerPlatform = $this->input('platform') ?: Thread::CRAWLER_DATA_PLATFORM_OF_WEIBO;
        $officialAccountUrl = $this->input('officialAccountUrl');
        $cookie = $this->input('cookie');
        $userAgent = $this->input('userAgent');

        if (empty($topic) && $this->crawlerPlatform != Thread::CRAWLER_DATA_PLATFORM_OF_WECHAT) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '请输入话题！');
        }
        $category = Category::query()->select('id')->orderBy('id', 'asc')->first()->toArray();
        if (empty($category)) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '缺少分类，请您先创建内容分类！');
        }

        $this->categoryId = $category['id'];
        if ($this->crawlerPlatform == Thread::CRAWLER_DATA_PLATFORM_OF_WECHAT) {
            if (empty($officialAccountUrl)) {
                $this->outPut(ResponseCode::INVALID_PARAMETER, '请输入公众号文章链接！');
            } elseif (count($officialAccountUrl) > Thread::IMPORT_WECHAT_DATA_LIMIT) {
                $this->outPut(ResponseCode::INVALID_PARAMETER, '公众号文章链接不可超过' . Thread::IMPORT_WECHAT_DATA_LIMIT . '条！');
            }
        }

        $number = $this->input('number');
        if ($this->crawlerPlatform != Thread::CRAWLER_DATA_PLATFORM_OF_WECHAT && ($number <= 0 || $number > 1000)) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '请输入正确的导入条数！');
        }

        if ($this->crawlerPlatform == Thread::CRAWLER_DATA_PLATFORM_OF_ZSXQ) {
            if (empty($cookie)) {
                $this->outPut(ResponseCode::INVALID_PARAMETER, '请输入cookie！');
            } elseif (empty($userAgent)) {
                $this->outPut(ResponseCode::INVALID_PARAMETER, '请输入userAgent！');
            }
        }

        $publicPath = public_path();
        $lockPath = $publicPath . DIRECTORY_SEPARATOR . 'crawlerSplQueueLock.conf';
        if (file_exists($lockPath)) {
            $lockFileContent = $this->getLockFileContent($lockPath);
            if ($lockFileContent['runtime'] < Thread::CREATE_CRAWLER_DATA_LIMIT_MINUTE_TIME && $lockFileContent['status'] == Thread::IMPORT_PROCESSING) {
                $this->outPut(ResponseCode::RESOURCE_IN_USE, "当前内容[{$lockFileContent['topic']}]正在导入，请勿重复操作！当前已执行" . $lockFileContent['runtime'] . "分钟。");
            } else if ($lockFileContent['runtime'] > Thread::CREATE_CRAWLER_DATA_LIMIT_MINUTE_TIME) {
                $this->changeLockFileContent($lockPath, 0, Thread::PROCESS_OF_START_INSERT_CRAWLER_DATA, Thread::IMPORT_TIMEOUT_ENDING, $lockFileContent['topic']);
                app('cache')->clear();
                $this->outPut(ResponseCode::INVALID_PARAMETER, "内容[{$lockFileContent['topic']}]导入时间过长，导入失败！");
            }
        }

        $inputData = [
            'topic'    => $topic,
            'platform' => $this->crawlerPlatform,
            'number'   => $number,
            'categoryId' => $this->categoryId,
            'officialAccountUrl' => $officialAccountUrl,
            'cookie' => $cookie,
            'userAgent' => $userAgent
        ];

        $crawlerSplQueue = new \SplQueue();
        $crawlerSplQueue->enqueue($inputData);
        app('cache')->put(CacheKey::CRAWLER_SPLQUEUE_INPUT_DATA, $crawlerSplQueue);
        $this->outPut(ResponseCode::SUCCESS, '内容导入开始！');
    }
}
