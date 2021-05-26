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

use App\Commands\Users\GenJwtToken;
use App\Common\AuthUtils;
use App\Common\ResponseCode;
use App\Models\SessionToken;
use App\Models\User;
use App\Models\UserWechat;
use App\User\Bound;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Contracts\Socialite\Factory;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;

class WechatH5BindController extends AuthBaseController
{
    use AssertPermissionTrait;
    protected $socialite;
    protected $validation;
    protected $db;
    protected $bound;
    protected $bus;

    public function __construct(
        Factory             $socialite,
        ValidationFactory   $validation,
        ConnectionInterface $db,
        Bound               $bound,
        Dispatcher          $bus
    ){
        $this->socialite    = $socialite;
        $this->validation   = $validation;
        $this->db           = $db;
        $this->bound        = $bound;
        $this->bus          = $bus;
    }

    public function main()
    {
        $wxuser         = $this->getWxuser();
        $sessionToken   = $this->inPut('sessionToken');
        $token          = SessionToken::get($sessionToken);
        $type           = $this->inPut('type');//用于区分sessionToken来源于pc还是h5
        $actor          = !empty($token->user) ? $token->user : $this->user;

        if (empty($actor)) {
            $this->outPut(ResponseCode::NOT_FOUND_USER);
        }

        $this->db->beginTransaction();
        try {
            /** @var UserWechat $wechatUser */
            $wechatUser = UserWechat::query()
                ->where('mp_openid', $wxuser->getId())
                ->orWhere('unionid', Arr::get($wxuser->getRaw(), 'unionid'))
                ->lockForUpdate()
                ->first();
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->outPut(ResponseCode::NET_ERROR,
                          ResponseCode::$codeMap[ResponseCode::NET_ERROR],
                          $e->getMessage()
            );
        }

        if (!$wechatUser || !$wechatUser->user) {
            if (!$wechatUser) {
                $wechatUser = new UserWechat();
            }

            $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), $actor));
            // 登陆用户且没有绑定||换绑微信 添加微信绑定关系
            $wechatUser->user_id = $actor->id;
            $wechatUser->setRelation('user', $actor);
            $wechatUser->save();
            $this->updateUserBindType($actor,AuthUtils::WECHAT);
            if (empty($actor->nickname)) {
                $actor->nickname = $wechatUser->nickname;
                $actor->save();
            }
            $this->db->commit();

            // PC扫码使用
            if (!empty($sessionToken) && $type == 'pc') {
                $accessToken = $this->getAccessToken($wechatUser->user);
                $wechatUser = [
                    'nickname'      =>  $wechatUser['nickname'],
                    'headimgurl'    =>  $wechatUser['headimgurl']
                ];
                $this->bound->bindVoid($sessionToken, $wechatUser, $accessToken);

            }

            //用于用户名登录绑定微信使用
            if (!empty($token->user) && $type == 'h5') {
                if (empty($actor->username)) {
                    $this->outPut(ResponseCode::USERNAME_NOT_NULL);
                }
                //token生成
                $accessToken = $this->getAccessToken($actor);
                $result = $this->camelData(collect($accessToken));
                $result = $this->addUserInfo($actor, $result);
                $this->outPut(ResponseCode::SUCCESS, '', $result);
            }

            $this->outPut(ResponseCode::SUCCESS, '', []);

        } else {
            $this->db->rollBack();
            $this->outPut(ResponseCode::ACCOUNT_HAS_BEEN_BOUND);
        }
    }
}