<?php

namespace App\Api\Controller\NotificationV3;

use App\Common\ResponseCode;
use App\Models\NotificationTpl;
use App\Repositories\UserRepository;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;

class ListNotificationTplV3Controller extends DzqController
{
    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $actor = $this->user;
        if (!$actor->isAdmin()) {
            throw new PermissionDeniedException('没有权限');
        }
        return true;
    }


    public function main()
    {
        $page = $this->inPut('page');
        $perPage = $this->inPut('perPage');

        $tpl = NotificationTpl::query()->select('id', 'status', 'type', 'type_name', 'is_error', 'error_msg');

        $pageData = $this->pagination($page, $perPage, $tpl, false);

        foreach ($pageData['pageData'] as $k=>$v) {
            $pageData['pageData'][$k]['type'] = NotificationTpl::enumTypeName($v->type);
        }

        $this->outPut(ResponseCode::SUCCESS, '', $this->camelData($pageData));
    }
}
