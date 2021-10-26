<?php
namespace App\Api\Controller\ThreadsV3;

use App\Api\Controller\SettingsV3\CdnTrait;
use App\Common\ResponseCode;
use Discuz\Base\DzqAdminController;

class PurgeCdnCacheController extends DzqAdminController
{
    use CdnTrait;

    public function main()
    {
        $response = $this->purgeCdnPathCache();
        $msg = '';
        $response == false && $msg = 'CDN缓存刷新失败';
        $this->outPut(ResponseCode::SUCCESS, $msg, [$response]);
    }
}
