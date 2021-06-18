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

use App\Commands\Users\UpdateClientUser;
use App\Common\CacheKey;
use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use Discuz\Base\DzqCache;
use Discuz\Base\DzqController;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;

class UpdateUsersController extends DzqController
{

    public function clearCache($user)
    {
        DzqCache::delHashKey(CacheKey::LIST_THREADS_V3_USERS, $user->id);
    }

    protected $bus;
    protected $settings;

    public function __construct(Dispatcher $bus, SettingsRepository $settings)
    {
        $this->bus = $bus;
        $this->settings = $settings;
    }

    // 权限检查
    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $actor = $this->user;
        if ($actor->isGuest()) {
            $this->outPut(ResponseCode::JUMP_TO_LOGIN);
        }
        return true;
    }

    public function main()
    {
        $id = $this->user->id;
        if (empty($id)) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '');
        }
        $nickname = $this->inPut('nickname');
        $username = $this->inPut('username');
        $password = $this->inPut('password');
        $newPassword = $this->inPut('newPassword');
        $passwordConfirmation = $this->inPut('passwordConfirmation');
        $payPassword = $this->inPut('payPassword');
        $payPasswordConfirmation = $this->inPut('payPasswordConfirmation');
        $payPasswordToken = $this->inPut('payPasswordToken');

        $registerReason = $this->inPut('registerReason');

        $requestData = [];
        if (!empty($username)) {
            $requestData['username'] = $username;
        }
        if (!empty($password)) {
            $requestData['password'] = $password;
        }
        if (!empty($newPassword)) {
            $requestData['newPassword'] = $newPassword;
        }
        if (!empty($passwordConfirmation)) {
            $requestData['password_confirmation'] = $passwordConfirmation;
        }
        if (!empty($payPassword)) {
            $requestData['payPassword'] = $payPassword;
        }
        if (!empty($payPasswordConfirmation)) {
            $requestData['pay_password_confirmation'] = $payPasswordConfirmation;
        }
        if (!empty($payPasswordToken)) {
            $requestData['pay_password_token'] = $payPasswordToken;
        }

        $getRequestData = json_decode(file_get_contents("php://input"), TRUE);
        if (Arr::has($getRequestData, 'signature')){
            $requestData['signature'] = $this->inPut('signature');
        }

        if (!empty($registerReason)) {
            $requestData['register_reason'] = $registerReason;
        }
        if (!empty($nickname)) {
            $requestData['nickname'] = $nickname;
        }


        $result = $this->bus->dispatch(
            new UpdateClientUser(
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
        $returnData['background'] = "";
        if (!empty($data['background'])) {
            $returnData['background'] = $this->getBackground($data['background']);
        }

        return $this->outPut(ResponseCode::SUCCESS, '', $returnData);
    }

    protected function getBackground($background)
    {
        $url = $this->request->getUri();
        $port = $url->getPort();
        $port = $port == null ? '' : ':' . $port;
        $path = $url->getScheme() . '://' . $url->getHost() . $port . '/';
        $returnData['background'] = $path . "/storage/background/" . $background;
        if (strpos($background, "cos://") !== false) {
            $background = str_replace("cos://", "", $background);
            $remoteServer = $this->settings->get('qcloud_cos_cdn_url', 'qcloud', true);
            $right = substr($remoteServer, -1);
            if ("/" == $right) {
                $remoteServer = substr($remoteServer, 0, strlen($remoteServer) - 1);
            }
            $returnData['background'] = $remoteServer . "/public/background/" . $background;
        }
        return $returnData['background'];
    }
}
