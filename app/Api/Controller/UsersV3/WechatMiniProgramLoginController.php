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

use App\Commands\Users\AutoRegisterUser;
use App\Commands\Users\GenJwtToken;
use App\Common\ResponseCode;
use App\Events\Users\Logind;
use App\Exceptions\NoUserException;
use App\Models\User;
use App\Settings\SettingsRepository;
use App\User\Bind;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Guest;
use Discuz\Wechat\EasyWechatTrait;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

class WechatMiniProgramLoginController extends AuthBaseController
{
    use AssertPermissionTrait;
    use EasyWechatTrait;

    protected $bus;
    protected $validation;
    protected $events;
    protected $settings;
    protected $bind;
    protected $db;

    public function __construct(
        Dispatcher          $bus,
        ValidationFactory   $validation,
        Events              $events,
        SettingsRepository  $settings,
        Bind                $bind,
        ConnectionInterface $db
    ){
        $this->bus          = $bus;
        $this->validation   = $validation;
        $this->events       = $events;
        $this->settings     = $settings;
        $this->bind         = $bind;
        $this->db           = $db;
    }

    public function main()
    {
        $param          = $this->getWechatMiniProgramParam();
        $jsCode         = $param['jsCode'];
        $iv             = $param['iv'];
        $encryptedData  = $param['encryptedData'];
        $user           = !$this->user->isGuest() ? $this->user : new Guest();
        $inviteCode     = $this->inPut('inviteCode');

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
                          ResponseCode::$codeMap[ResponseCode::NET_ERROR],
                          $e->getMessage()
            );
        }

        if ($wechatUser->user_id) {
            //已绑定的用户登陆
            $user = $wechatUser->user;

            //用户被删除
            if (!$user) {
                $this->db->rollback();
                $this->outPut(ResponseCode::BIND_ERROR,
                              ResponseCode::$codeMap[ResponseCode::BIND_ERROR]
                );
            }
        } else {
            //未绑定的用户自动注册
            if (!(bool)$this->settings->get('register_close')) {
                $this->db->rollback();
                $this->outPut(ResponseCode::REGISTER_CLOSE,
                              ResponseCode::$codeMap[ResponseCode::REGISTER_CLOSE]
                );
            }

            //注册邀请码
            $data = array();
            $data['code']               = $inviteCode;
            $data['username']           = Str::of($wechatUser->nickname)->substr(0, 15);
            $data['register_reason']    = trans('user.register_by_wechat_miniprogram');
            $user = $this->bus->dispatch(
                new AutoRegisterUser($this->user, $data)
            );
            $wechatUser->user_id = $user->id;
            // 先设置关系再save，为了同步微信头像
            $wechatUser->setRelation('user', $user);
            $wechatUser->save();

            $this->db->commit();
        }
        $this->db->commit();

        //创建 token
        $params = [
            'username' => $user->username,
            'password' => ''
        ];

        $response = $this->bus->dispatch(
            new GenJwtToken($params)
        );

        //小程序无感登录，待审核状态
        if ($response->getStatusCode() === 200) {
            if($user->status!=User::STATUS_MOD){
                $this->events->dispatch(new Logind($user));
            }
        }

        $result = $this->camelData(collect(json_decode($response->getBody())));
        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }
}
