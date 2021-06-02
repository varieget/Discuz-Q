<?php

namespace App\Api\Controller\DialogV3;

use App\Common\ResponseCode;
use App\Models\DialogMessage;
use App\Models\User;
use App\Providers\DialogMessageServiceProvider;
use App\Repositories\UserRepository;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\NotAuthenticatedException;
use Discuz\Base\DzqController;

class DialogRecordController extends DzqController
{
    use AssertPermissionTrait;

    public $providers = [
        DialogMessageServiceProvider::class,
    ];

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if ($this->user->isGuest()) {
            throw new NotAuthenticatedException();
        }
        return true;
    }

    public function main()
    {
        $actor = $this->user;

        $username = $this->inPut('username');
        $user = User::query()->where('username', $username)->pluck('id')->toArray();

        if(empty($user)){
            $this->outPut(ResponseCode::INVALID_PARAMETER,'缺少必传参数');
        }
        $userId = $user[0];
        if ($userId == $actor->id) {
            $this->outPut(ResponseCode::INVALID_PARAMETER,'自己不能给自己发私信');
        }

        $dialog =DialogMessage::query()->distinct('user_id')
            ->where('user_id' ,'=',$userId)->first('dialog_id');
        $data = [];
        if (empty($dialog)){
            $data['dialog_id'] = '';
        }else{
            $data['dialog_id'] = $dialog['dialog_id'];
        }

        $this->outPut(ResponseCode::SUCCESS,'', $data);
    }

}
