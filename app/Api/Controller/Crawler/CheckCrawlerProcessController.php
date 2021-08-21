<?php


namespace App\Api\Controller\Crawler;

use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;

class CheckCrawlerProcessController extends DzqController
{
    use CrawlerTrait;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return $this->user->isAdmin();
    }

    public function main()
    {
        $publicPath = public_path();
        $lockPath = $publicPath . DIRECTORY_SEPARATOR . 'crawlerSplQueueLock.conf';
        $lockFileContent = [];
        try {
            $lockFileContent = $this->getLockFileContent($lockPath);
        } catch (\Exception $e) {
            app('log')->info('error_check_crawler_process:' . $e->getMessage());
        }

        $this->outPut(ResponseCode::SUCCESS, '', $lockFileContent);
    }
}