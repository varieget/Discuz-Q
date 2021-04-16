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
use App\Common\ResponseCode;
use App\Models\SessionToken;
use App\Models\User;
use App\Models\UserWechat;
use Illuminate\Support\Arr;

/**
 * 过渡阶段微信登录
 * Class WechatTransitionH5LoginController
 * @package App\Api\Controller\UsersV3
 */
class WechatTransitionH5LoginController extends AbstractWechatH5LoginBaseController
{
    public function main()
    {
        /** 获取授权后微信用户基础信息*/
        $wxuser = $this->getWxUser();

        $this->db->beginTransaction();
        /** @var UserWechat $wechatUser */
        $wechatUser = UserWechat::query()
            ->where('mp_openid', $wxuser->getId())
            ->orWhere('unionid', Arr::get($wxuser->getRaw(), 'unionid'))
            ->lockForUpdate()
            ->first();
        // 微信信息不存在
        if(! $wechatUser) {
            $wechatUser = new UserWechat();
        }

        if(is_null($wechatUser->user)) {
            // 站点关闭注册
            if (!(bool)$this->settings->get('register_close')) {
                $this->db->rollBack();
                $this->outPut(ResponseCode::REGISTER_CLOSE,
                    ResponseCode::$codeMap[ResponseCode::REGISTER_CLOSE]
                );
            }
            $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), new User()));
            $wechatUser->save();//微信信息写入user_wechats
            $this->db->commit();
            //生成sessionToken,并把user_wechats 信息写入session_token
            $token = SessionToken::generate(SessionToken::WECHAT_TRANSITION_LOGIN, (array)$wechatUser);
            $token->save();
            $sessionToken = $token->token;
            //把token返回用户绑定用户使用
            $this->outPut(ResponseCode::NEED_BIND_USER_OR_CREATE_USER, '', ['sessionToken' => $sessionToken]);
        }

        // 登陆用户和微信绑定相同，更新微信信息
        $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), $wechatUser->user));
        $wechatUser->save();
        $this->db->commit();

        // 生成token
        $params = [
            'username' => $wechatUser->user->username,
            'password' => ''
        ];
        GenJwtToken::setUid($wechatUser->user->id);
        $response = $this->bus->dispatch(
            new GenJwtToken($params)
        );
        $accessToken = json_decode($response->getBody());
        $this->outPut(ResponseCode::SUCCESS, '', $accessToken);
    }
}
