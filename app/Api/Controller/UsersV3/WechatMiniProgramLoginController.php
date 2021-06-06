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
use App\Common\AuthUtils;
use App\Common\ResponseCode;
use App\Events\Users\Logind;
use App\Exceptions\NoUserException;
use App\Models\SessionToken;
use App\Models\User;
use App\Models\UserWechat;
use App\Notifications\Messages\Wechat\RegisterWechatMessage;
use App\Notifications\System;
use App\Repositories\UserRepository;
use App\Settings\SettingsRepository;
use App\User\Bind;
use App\User\Bound;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Guest;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

class WechatMiniProgramLoginController extends AuthBaseController
{
    use AssertPermissionTrait;


    protected $bus;
    protected $validation;
    protected $events;
    protected $settings;
    protected $bind;
    protected $bound;
    protected $db;
    protected $miniParam;
    protected $miniUser;
    protected $app;

    public function __construct(
        Dispatcher          $bus,
        ValidationFactory   $validation,
        Events              $events,
        SettingsRepository  $settings,
        Bind                $bind,
        Bound               $bound,
        ConnectionInterface $db
    ){
        $this->bus          = $bus;
        $this->validation   = $validation;
        $this->events       = $events;
        $this->settings     = $settings;
        $this->bind         = $bind;
        $this->bound        = $bound;
        $this->db           = $db;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return true;
    }

    public function main()
    {
        try {
            $this->miniParam    = $this->getWechatMiniProgramParam();
            $sessionToken       = $this->inPut('sessionToken');
            $user               = !$this->user->isGuest() ? $this->user : new Guest();
            $this->miniUser     = $user;
            $inviteCode         = $this->inPut('inviteCode');
        } catch (Exception $e) {
            app('errorLog')->info('requestId：' . $this->requestId . '-' . '小程序参数获取接口异常-WechatMiniProgramLoginController： '
                                  .';inviteCode:'.$this->inPut('inviteCode')
                                  .';sessionToken:'.$this->inPut('sessionToken')
                                  .';userId:'.$this->user->id . ';异常：' . $e->getMessage());
            return $this->outPut(ResponseCode::INTERNAL_ERROR, '小程序参数获取接口异常');
        }
        //过渡开关打开
        if((bool)$this->settings->get('is_need_transition') && empty($sessionToken)) {
            $this->transitionLoginLogicVoid();
        }

        // 绑定小程序
        $this->db->beginTransaction();
        try {
            $wechatUser = $this->getMiniWechatUser(
                $this->miniParam['jsCode'],
                $this->miniParam['iv'],
                $this->miniParam['encryptedData'],
                $user
            );

            if (!$wechatUser || !$wechatUser->user) {
                // 更新微信用户信息
                if (!$wechatUser) {
                    $wechatUser = new UserWechat();
                }
    //            $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), $actor));

                // 自动注册
                if ($user->isGuest()) {
                    // 站点关闭注册
                    if (!(bool)$this->settings->get('register_close')) {
                        $this->db->rollBack();
                        $this->outPut(ResponseCode::REGISTER_CLOSE);
                    }

                    $data['code']               = $inviteCode;
                    $data['username']           = Str::of($wechatUser->nickname)->substr(0, 15);
                    $data['register_reason']    = trans('user.register_by_wechat_miniprogram');
                    $user = $this->bus->dispatch(
                        new AutoRegisterUser($this->user, $data)
                    );
                    $wechatUser->user_id = $user->id;
                    // 先设置关系，为了同步微信头像
                    $wechatUser->setRelation('user', $user);
                    $wechatUser->save();

                    $this->updateUserBindType($user,AuthUtils::WECHAT);
                    $this->db->commit();

                    // 判断是否开启了注册审核
    //                if (!(bool)$this->settings->get('register_validate')) {
    //                    // Tag 发送通知 (在注册绑定微信后 发送注册微信通知)
    //                    $user->setRelation('wechat', $wechatUser);
    //                    $user->notify(new System(RegisterWechatMessage::class, $user, ['send_type' => 'wechat']));
    //                }
                } else {
                    if (!$user->isGuest() && is_null($user->wechat)) {
                        // 登陆用户且没有绑定||换绑微信 添加微信绑定关系
                        $wechatUser->user_id = $user->id;
                        $wechatUser->setRelation('user', $user);
                        $wechatUser->save();

                        $this->updateUserBindType($user,AuthUtils::WECHAT);
                        $this->db->commit();
                    }
                }
            } else {
                // 登陆用户和微信绑定不同时，微信已绑定用户，抛出异常
                if (!$user->isGuest() && $user->id != $wechatUser->user_id) {
                    $this->db->rollBack();
                    $this->outPut(ResponseCode::ACCOUNT_HAS_BEEN_BOUND);
                }

                // 登陆用户和微信绑定相同，更新微信信息
    //            $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), $wechatUser->user));
                $wechatUser->save();
                $this->db->commit();
            }

            if (empty($wechatUser) || empty($wechatUser->user)) {
                $this->outPut(ResponseCode::INVALID_PARAMETER);
            }

            //创建 token
            $params = [
                'username' => $wechatUser->user->username,
                'password' => ''
            ];

            $response = $this->bus->dispatch(
                new GenJwtToken($params)
            );

            //小程序无感登录，待审核状态
            if ($response->getStatusCode() === 200) {
                if($wechatUser->user->status!=User::STATUS_MOD){
                    $this->events->dispatch(new Logind($wechatUser->user));
                }
            }

            $accessToken = json_decode($response->getBody());

            // bound
            if ($sessionToken) {
                $accessToken = $this->bound->pcLogin($sessionToken, (array)$accessToken, ['user_id' => $wechatUser->user->id]);

                $this->updateUserBindType($wechatUser->user,AuthUtils::WECHAT);
            }

            $result = $this->camelData(collect($accessToken));
            $result = $this->addUserInfo($wechatUser->user, $result);
            $this->outPut(ResponseCode::SUCCESS, '', $result);
        } catch (Exception $e) {
            app('errorLog')->info('requestId：' . $this->requestId . '-' . '小程序登录接口异常-WechatMiniProgramLoginController： ' . $e->getMessage());
            $this->db->rollBack();
            $this->outPut(ResponseCode::INTERNAL_ERROR,'小程序登录接口异常');
        }
    }

    /**
     * 过渡阶段微信登录，过渡开关打开走此流程
     */
    private function transitionLoginLogicVoid()
    {
        $this->db->beginTransaction();
        try {
            /** @var UserWechat $wechatUser */
            $wechatUser = $this->getMiniWechatUser(
                $this->miniParam['jsCode'],
                $this->miniParam['iv'],
                $this->miniParam['encryptedData'],
                $this->miniUser
            );

            // 微信信息不存在
            if(! $wechatUser) {
                $wechatUser = new UserWechat();
            }
            $userWechatId = $wechatUser ? $wechatUser->id : 0;
            if(is_null($wechatUser->user)) {
                // 站点关闭注册
                if (!(bool)$this->settings->get('register_close')) {
                    $this->db->rollBack();
                    $this->outPut(ResponseCode::REGISTER_CLOSE);
                }
    //            $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), new User()));
                $wechatUser->save();//微信信息写入user_wechats
                $userWechatId = $wechatUser->id ? $wechatUser->id : $userWechatId;
                $this->db->commit();
                //生成sessionToken,并把user_wechats 信息写入session_token
                $token = SessionToken::generate(SessionToken::WECHAT_TRANSITION_LOGIN, ['user_wechat_id' => $userWechatId], null, 1800);
                $token->save();
                $sessionToken = $token->token;

                //把token返回用户绑定用户使用
                $this->outPut(ResponseCode::NEED_BIND_USER_OR_CREATE_USER, '', ['sessionToken' => $sessionToken, 'nickname' => $wechatUser->nickname]);
            }

            // 登陆用户和微信绑定相同，更新微信信息
    //        $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), $wechatUser->user));
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

            $result = $this->camelData(collect($accessToken));

            $result = $this->addUserInfo($wechatUser->user, $result);

            $this->outPut(ResponseCode::SUCCESS, '', $result);
        } catch (Exception $e) {
            app('errorLog')->info('requestId：' . $this->requestId . '-' . '小程序登录接口异常-WechatMiniProgramLoginController： ' . $e->getMessage());
            $this->db->rollBack();
            $this->outPut(ResponseCode::INTERNAL_ERROR,'小程序登录接口异常');
        }
    }
}
