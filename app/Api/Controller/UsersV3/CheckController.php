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

use App\Censor\Censor;
use App\Common\ResponseCode;
use App\Models\User;
use Discuz\Base\DzqController;
use Discuz\Foundation\EventsDispatchTrait;
use Discuz\Auth\AssertPermissionTrait;

class CheckController extends DzqController
{
    use EventsDispatchTrait;
    use AssertPermissionTrait;

    protected $censor;

    public function __construct(Censor $censor)
    {
        $this->censor = $censor;
    }


    public function main()
    {
        $data = [
            'username' => $this->inPut('username')
        ];

        $isExist = false;

        $this->censor->checkText($data['username'], 'username');

        if(empty($data['username']) || mb_strlen($data['username'],'UTF8') > 15){
            $isExist = true;
        }

        $userNameCount = User::query()->where('username',$data['username'])->count();
        if($userNameCount > 0){
            $isExist = true;
        }

        return $this->outPut(ResponseCode::SUCCESS, '',$isExist);
    }

}
