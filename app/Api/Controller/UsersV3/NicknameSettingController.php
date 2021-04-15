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


use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use App\Censor\Censor;

class NicknameSettingController extends AuthBaseController
{
    protected $userRepository;

    protected $censor;
    public function __construct(UserRepository $userRepository, Censor $censor)
    {
        $this->userRepository = $userRepository;
        $this->censor = $censor;
    }

    public function main()
    {
        $nickname = $this->inPut('nickname');
        $this->dzqValidate([
            'nickname'      => $nickname,
        ], [
            'nickname'      => 'required',
        ]);
        $this->censor->checkText($nickname);
        $user = $this->userRepository->findOrFail($this->user->id, $this->user);
        $user->changeNickname($nickname);
        $user->save();

        return $this->outPut(ResponseCode::SUCCESS);
    }

}
