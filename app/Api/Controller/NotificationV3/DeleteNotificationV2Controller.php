<?php

namespace App\Api\Controller\NotificationV3;

use App\Common\ResponseCode;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\NotAuthenticatedException;
use Discuz\Base\DzqController;
use Illuminate\Notifications\DatabaseNotification;

class DeleteNotificationV2Controller extends DzqController
{
    use AssertPermissionTrait;

    public function main()
    {
        $ids = explode(',', $this->inPut('id'));

        $user = $this->user;
        try {
            $this->assertRegistered($user);
        } catch (NotAuthenticatedException $e) {
            $this->outPut(ResponseCode::JUMP_TO_LOGIN);
        }

        $deleted = DatabaseNotification::query()
            ->whereIn('id', $ids)
            ->where('notifiable_id', $user->id)
            ->forceDelete();

        $this->outPut($deleted > 0 ? ResponseCode::SUCCESS : ResponseCode::INVALID_PARAMETER);
    }
}
