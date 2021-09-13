<?php


namespace App\Api\Controller\Crawler;

use App\Common\ResponseCode;
use App\Repositories\UserRepository;
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