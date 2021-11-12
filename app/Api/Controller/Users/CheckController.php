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

namespace App\Api\Controller\Users;

use App\Censor\Censor;
use App\Common\ResponseCode;
use App\Models\User;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Discuz\Base\DzqLog;
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

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return true;
    }


    public function main()
    {
        try {
            $username = $this->inPut('username');
            $nickname = $this->inPut('nickname');
            if (!empty($username)) {
                $this->checkName('username', $username);
            }
            if (!empty($nickname)) {
                $this->checkName('nickname', $nickname);
            }

            $this->outPut(ResponseCode::SUCCESS);
        } catch (\Exception $e) {
            DzqLog::error('username_nickname_check_api_error', [
                'username' => $this->inPut('username'),
                'nickname' => $this->inPut('nickname')
            ], $e->getMessage());
            $this->outPut(ResponseCode::INTERNAL_ERROR, '用户昵称检测接口异常');
        }
    }

    public function checkName($checkField = '', $fieldValue = '', $isThrow = true, $removeId = 0, $isAutoRegister = false)
    {
        $allowFields = [
            'username' => '用户名',
            'nickname' => '昵称'
        ];
        $res = [
            'field' => $checkField,
            'value' => $fieldValue,
            'errorCode' => 0,
            'errorMsg' => ''
        ];

        if (!in_array($res['field'], array_keys($allowFields))) {
            $res['errorCode'] = ResponseCode::INVALID_PARAMETER;
            $res['errorMsg'] = '未被允许的检测字段';
            return $res;
        }

        //去除字符串中空格
        $res['value'] = preg_replace('/\s/ui', '', $res['value']);

        //敏感词检测
        $censor = app()->make(Censor::class);
        $res['value'] = $censor->checkText($res['value'], $res['field']);

        //重名校验
        $query = User::query()->where($res['field'], $res['value']);
        if (!empty($removeId)) {
            $query->where('id', '<>', $removeId);
        }
        $exists = $query->exists();

        if ($isAutoRegister == false) {
            //长度检查
            if (strlen($res['value']) == 0) {
                $res['errorCode'] = ResponseCode::USERNAME_NOT_NULL;
                $res['errorMsg'] = $allowFields[$res['field']].'不能为空';
            } elseif (mb_strlen($res['value'], 'UTF8') > 15) {
                $res['errorCode'] = ResponseCode::NAME_LENGTH_ERROR;
                $res['errorMsg'] = $allowFields[$res['field']].'长度超过15个字符';
            } elseif (!empty($exists)) {
                //重名检测
                $res['errorCode'] = ResponseCode::USERNAME_HAD_EXIST;
                $res['errorMsg'] = $allowFields[$res['field']].'已经存在';
            }
        } else {
            if (!empty($exists)) {
                $res['value'] = $res['field'] == 'username'
                    ? User::addStringToUsername($res['value'])
                    : User::addStringToNickname($res['value']);
            }
        }

        if ($isThrow == true && $res['errorCode'] != 0) {
            $this->outPut($res['errorCode'], $res['errorMsg']);
        }

        return $res;
    }
}
