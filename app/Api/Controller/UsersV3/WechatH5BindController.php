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
use App\User\Bound;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Contracts\Socialite\Factory;
use Exception;
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

    public function __construct(
        Factory             $socialite,
        ValidationFactory   $validation,
        ConnectionInterface $db,
        Bound               $bound
    ){
        $this->socialite    = $socialite;
        $this->validation   = $validation;
        $this->db           = $db;
        $this->bound        = $bound;
    }

    public function main()
    {
        $code           = $this->inPut('code');
        $sessionId      = $this->inPut('session_id');
        $sessionToken   = $this->inPut('session_token');

        //调试用
//        $sessionId      = $this->inPut('sessionId');

        $request        = $this ->request
                                ->withAttribute('session', new SessionToken())
                                ->withAttribute('sessionId', $sessionId);

        $this->dzqValidate([
                                'code'      => $code,
                                'sessionId' => $sessionId,
                            ], [
                                'code'      => 'required',
                                'sessionId' => 'required'
                            ]);

        $this->socialite->setRequest($request);

        $driver = $this->socialite->driver($this->getDriver());
        $wxuser = $driver->user();
//        $wxuser = UserWechat::query()
//                ->where('id', 2)
//                ->first();

        /** @var User $actor */
        $actor = $this->user;
//        $actor = User::query()
//                    ->where('id', 2)
//                    ->first();

        $this->db->beginTransaction();
        try {
            /** @var UserWechat $wechatUser */
            $wechatUser = UserWechat::query()
                ->where($this->getType(), $wxuser->getId())
                ->orWhere('unionid', Arr::get($wxuser->getRaw(), 'unionid'))
                ->lockForUpdate()
                ->first();
//            $wechatUser = '';
        } catch (Exception $e) {
            $this->db->rollBack();
        }
        $wechatlog = app('wechatLog');
        $wechatlog->info('wechat_info', [
            'wechat_user'   => $wechatUser == null ? '': $wechatUser->toArray(),
            'user_info'     => $wechatUser->user == null ? '' : $wechatUser->user->toArray()
        ]);

        if (!$wechatUser) {
            $wechatUser = new UserWechat();
            $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), $actor));
            // 登陆用户且没有绑定||换绑微信 添加微信绑定关系
            $wechatUser->user_id = $actor->id;
            $wechatUser->setRelation('user', $actor);
            $wechatUser->save();
            $this->db->commit();

            // PC扫码使用
            if ($sessionToken) {
                $accessToken = $this->bound->pcH5Bind($sessionToken, '', ['user_id' => $wechatUser->user->id]);
            }

            $this->outPut(ResponseCode::SUCCESS, '', []);
        } else {
            $this->db->rollBack();
            $this->outPut(ResponseCode::ACCOUNT_HAS_BEEN_BOUND,
                          ResponseCode::$codeMap[ResponseCode::ACCOUNT_HAS_BEEN_BOUND]
            );
        }
    }
}
