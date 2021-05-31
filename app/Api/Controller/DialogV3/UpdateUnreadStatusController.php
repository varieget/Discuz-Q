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

namespace App\Api\Controller\DialogV3;

use App\Models\DialogMessage;
use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use Discuz\Auth\Exception\NotAuthenticatedException;
use Discuz\Base\DzqController;

class UpdateUnreadStatusController extends DzqController
{
    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if ($this->user->isGuest()) {
            throw new NotAuthenticatedException();
        }
        return true;
    }

    public function main()
    {

        $data = $this->inPut('data');
        if(empty($data)) {
            return $this->outPut(ResponseCode::NET_ERROR);
        }

        foreach ($data as $key => $value) {
            try{
                $this->dzqValidate($value, [
                    'id'       => 'required|int|min:1',
                    'readStatus'   => 'required|int|in:1'
                ]);

                $dialog = DialogMessage::query()->where('user_id', $this->user->id)->findOrFail($value['id']);
                $dialog->read_status = $value['readStatus'];
                $dialog->save();
               // dump($dialog);die;
            } catch (\Exception $e) {
                 $this->outPut(ResponseCode::INTERNAL_ERROR, '修改出错', [$e->getMessage(), $value]);
            }
        }

        return $this->outPut(ResponseCode::SUCCESS,'','');
    }

}
