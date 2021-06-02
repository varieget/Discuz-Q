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
use App\Events\Users\ChangeUserStatus;
use App\Models\AdminActionLog;
use App\Models\User;
use App\Repositories\UserRepository;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Illuminate\Contracts\Bus\Dispatcher;

class UpdateUsersStatusController extends DzqController
{

    protected $bus;

    public function __construct(Dispatcher $bus, Dispatcher $events,User $actor)
    {
        $this->bus = $bus;
        $this->events = $events;
        $this->actor = $actor;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if (!$userRepo->canUserStatus($this->user)) {
            throw new PermissionDeniedException('您没有审核权限');
        }
        return true;
    }

    public function main()
    {
        $user = $this->user;
        $data = $this->inPut('data');

        foreach ($data as $value) {
            try {
                $user = User::query()->findOrFail($value['id']);
                $user->status  = $value['status'];
                $user->reject_reason  = $value['rejectReason'];

                $user->save();
                $resultData[] = [
                    'id'=>$value['id'],
                    'status'=>$value['status'],
                    'reject_reason'=>$value['rejectReason'],
                ];
            } catch (\Exception $e) {
                $this->outPut(ResponseCode::DB_ERROR, '审核失败');
                $this->info('审核失败：' . $e->getMessage());
            }

            $status_desc = array(
                '0' => '正常',
                '1' => '禁用',
                '2' => '审核中',
                '3' => '审核拒绝',
                '4' => '审核忽略'
            );

            AdminActionLog::createAdminActionLog(
                $user->id,
                '更改了用户【'. $user->username .'】的用户状态为【'. $status_desc[$value['status']] .'】'
            );

        }

        return $this->outPut(ResponseCode::SUCCESS,'', []);
    }


    //记录拒绝原因
    private function setRefuseMessage(User &$user,$refuseMessage){
        if ($user->status == User::STATUS_REFUSE) {
            $user->reject_reason = $refuseMessage;
            $user->save();
        }
    }


}
