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

namespace App\Api\Controller\UsersV3;

use App\Commands\Users\UpdateAdminUser;
use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Contracts\Bus\Dispatcher;

class UpdateAdminController extends DzqController
{

    protected $bus;
    protected $settings;

    public function __construct(Dispatcher $bus,SettingsRepository $settings)
    {
        $this->bus = $bus;
        $this->settings = $settings;
    }

    // 权限检查
    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return $this->user->isAdmin();
    }

    public function main()
    {
        $id = $this->inPut('id');

        if(empty($id)){
            $this->outPut(ResponseCode::INVALID_PARAMETER,'');
        }
        $username = $this->inPut('username');
        $password = $this->inPut('password');
        $newPassword = $this->inPut('newPassword');
        $passwordConfirmation = $this->inPut('passwordConfirmation');
        $mobile = $this->inPut('mobile');
        $status = $this->inPut('status');
        $expire_at = $this->inPut('expiredAt');
        $groupId = $this->inPut('groupId');
        $refuseMessage = $this->inPut('refuseMessage');

        $registerReason = $this->inPut('registerReason');


        $requestData = [];
        if(!empty($username)){
            $requestData['username'] = $username;
        }
        if(!empty($password)){
            $requestData['password'] = $password;
        }
        if(!empty($newPassword)){
            $requestData['newPassword'] = $newPassword;
        }
        if(!empty($passwordConfirmation)){
            $requestData['password_confirmation'] = $passwordConfirmation;
        }
        if(!empty($mobile)){
            $requestData['mobile'] = $mobile;
        }
        if(!empty($expire_at)){
            $requestData['expired_at'] = $expire_at;
        }
        if(!empty($groupId)){
            $requestData['groupId'] = $groupId;
        }
        if(!empty($refuseMessage)){
            $requestData['refuse_message'] = $refuseMessage;
        }

        $requestData['status'] = $status;

        $result = $this->bus->dispatch(
            new UpdateAdminUser(
                $id,
                $requestData,
                $this->user
            )
        );
        $data = $this->camelData($result);
        $returnData = [];
        $returnData['id'] = $data['id'];
        $returnData['username'] = $data['username'];
        $returnData['nickname'] = $data['nickname'];
        $returnData['mobile'] = $data['mobile'];
        $returnData['threadCount'] = $data['threadCount'];
        $returnData['followCount'] = $data['followCount'];
        $returnData['fansCount'] = $data['fansCount'];
        $returnData['likedCount'] = $data['likedCount'];
        $returnData['questionCount'] = $data['questionCount'];
        $returnData['avatar'] = $data['avatar'];

        if($data['background']){
            $url = $this->request->getUri();
            $port = $url->getPort();
            $port = $port == null ? '' : ':' . $port;
            $path = $url->getScheme() . '://' . $url->getHost() . $port . '/';
            $returnData['background'] = $path."storage/app/".$data['background'];
        }else{
            $returnData['background'] = "";
        }
        return $this->outPut(ResponseCode::SUCCESS,'', $returnData);
    }
}
