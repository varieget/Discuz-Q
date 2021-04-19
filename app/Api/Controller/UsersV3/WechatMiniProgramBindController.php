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

use App\Common\AuthUtils;
use App\Common\ResponseCode;
use App\User\Bind;
use App\User\Bound;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Guest;
use Discuz\Wechat\EasyWechatTrait;
use Exception;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\ConnectionInterface;

class WechatMiniProgramBindController extends AuthBaseController
{
    use AssertPermissionTrait;
    use EasyWechatTrait;

    protected $validation;
    protected $bind;
    protected $db;
    protected $bound;

    public function __construct(
        ValidationFactory   $validation,
        Bind                $bind,
        ConnectionInterface $db,
        Bound               $bound
    ){
        $this->validation   = $validation;
        $this->bind         = $bind;
        $this->db           = $db;
        $this->bound        = $bound;
    }

    public function main()
    {
        $param          = $this->getWechatMiniProgramParam();
        $jsCode         = $param['jsCode'];
        $iv             = $param['iv'];
        $encryptedData  = $param['encryptedData'];
        $user           = !$this->user->isGuest() ? $this->user : new Guest();
        $sessionToken   = $this->inPut('sessionToken');// PC扫码使用;

        // 绑定小程序
        $this->db->beginTransaction();
        try {
            $wechatUser = $this->bind->bindMiniprogram(
                $jsCode,
                $iv,
                $encryptedData,
                0,
                $user,
                true
            );
        } catch (Exception $e) {
            $this->db->rollback();
            $this->outPut(ResponseCode::NET_ERROR,
                          ResponseCode::$codeMap[ResponseCode::NET_ERROR] ,
                          $e->getMessage()
            );
        }

        if ($wechatUser->user_id) {
            $this->db->rollback();
            $this->outPut(ResponseCode::BIND_ERROR,
                          ResponseCode::$codeMap[ResponseCode::BIND_ERROR]
            );
        } else {
            $wechatUser->user_id = $user->id;
            // 先设置关系再save，为了同步微信头像
            $wechatUser->setRelation('user', $user);
            $wechatUser->save();

            $this->updateUserBindType($user, AuthUtils::WECHAT);

            $this->db->commit();

            // PC扫码使用
            if ($sessionToken) {
                $this->bound->bindVoid($sessionToken, $wechatUser);
            }
        }
        $this->outPut(ResponseCode::SUCCESS, '', []);
    }
}
