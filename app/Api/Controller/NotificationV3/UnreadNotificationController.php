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

namespace App\Api\Controller\NotificationV3;


use App\Common\ResponseCode;
use App\Models\User;
use App\Repositories\UserFollowRepository;
use App\Repositories\UserRepository;

class UnreadNotificationController extends \Discuz\Base\DzqController
{

    protected $users;

    protected $userFollow;

    protected $settings;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function main()
    {
        $actor = $this->user;
        if(! $actor || $actor->id) {
            return $this->outPut(ResponseCode::LOGIN_FAILED);
        }

        $user = $this->users->findOrFail($actor->id, $actor);
        $data['unreadNotifications'] = $user->getUnreadNotificationCount();
        $data['typeUnreadNotifications'] = $user->getUnreadTypesNotificationCount();

    }
}
