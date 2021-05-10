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
use App\Models\SessionToken;
use App\Models\User;
use App\Models\UserWechat;
use App\User\Bind;
use App\User\Bound;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Guest;
use Discuz\Contracts\Socialite\Factory;
use Discuz\Wechat\EasyWechatTrait;
use Exception;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\ConnectionInterface;

class WechatMiniProgramRebindController extends AuthBaseController
{
    use AssertPermissionTrait;
    use EasyWechatTrait;

    protected $socialite;
    protected $validation;
    protected $bind;
    protected $db;
    protected $bound;

    public function __construct(
        Factory             $socialite,
        ValidationFactory   $validation,
        Bind                $bind,
        ConnectionInterface $db,
        Bound               $bound
    ){
        $this->socialite    = $socialite;
        $this->validation   = $validation;
        $this->bind         = $bind;
        $this->db           = $db;
        $this->bound        = $bound;
    }

    public function main()
    {
        $param          = $this->getWechatMiniProgramParam();
        $sessionToken   = $this->inPut('sessionToken');// PC扫码使用
//        $sessionToken   = 'GJt8B1AxRjhLHR1VGJ2ZpsW5mLa6aRP2';
        $token          = SessionToken::get($sessionToken);
        $actor          = !empty($token->user) ? $token->user : $this->user;

        if (empty($actor) || $actor->isGuest() || is_null($actor->wechat)) {
            $this->outPut(ResponseCode::NOT_FOUND_USER);
        }

        if (is_null($actor->wechat)) {
            $this->outPut(ResponseCode::PC_REBIND_ERROR);
        }

        // 绑定小程序
        $this->db->beginTransaction();
        try {
            $app = $this->miniProgram();
            $wechatUser = $this->getMiniWechatUser(
                $app,
                $param['jsCode'],
                $param['iv'],
                $param['encryptedData']
            );
        } catch (Exception $e) {
            $this->db->rollback();
            $this->outPut(ResponseCode::NET_ERROR,
                          ResponseCode::$codeMap[ResponseCode::NET_ERROR],
                          $e->getMessage()
            );
        }

        if (!$wechatUser || !$wechatUser->user) {
            if (!$wechatUser) {
                $wechatUser = new UserWechat();
            }

            //删除用户原先绑定的微信信息
            UserWechat::query()->where('user_id', $actor->id)->delete();

            $wechatUser->user_id = $actor->id;
            // 先设置关系再save，为了同步微信头像
            $wechatUser->setRelation('user', $actor);
            $wechatUser->save();

            $this->db->commit();

            // PC扫码使用
            if ($sessionToken) {
                $this->bound->rebindVoid($sessionToken, $wechatUser);
            }

            $this->outPut(ResponseCode::SUCCESS, '', []);
        } else {
            $this->db->rollBack();
            $this->outPut(ResponseCode::ACCOUNT_HAS_BEEN_BOUND);
        }
    }
}
