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
use App\User\Bound;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Contracts\Socialite\Factory;
class WechatH5LoginController extends AuthBaseController
{

    use AssertPermissionTrait;
    protected $socialite;
    protected $bus;
    protected $validation;
    protected $events;
    protected $settings;
    protected $bound;
    protected $db;

    public function __construct(
        Factory             $socialite,
        Dispatcher          $bus,
        ValidationFactory   $validation,
        Events              $events,
        SettingsRepository  $settings,
        Bound               $bound,
        ConnectionInterface $db
    ){
        $this->socialite    = $socialite;
        $this->bus          = $bus;
        $this->validation   = $validation;
        $this->events       = $events;
        $this->settings     = $settings;
        $this->bound        = $bound;
        $this->db           = $db;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return true;
    }

    public function main()
    {
            //获取授权后微信用户信息
        try {
            $wxuser         = $this->getWxuser();
            $inviteCode     = $this->inPut('inviteCode');//邀请码非必须存在
            $sessionToken   = $this->inPut('sessionToken');//PC扫码使用，非必须存在
            $actor          = $this->user;
        } catch (Exception $e) {
            app('errorLog')->info('requestId：' . $this->requestId . '-' . 'H5登录获取wx用户接口异常-WechatH5LoginController： ' . $e->getMessage());
            return $this->outPut(ResponseCode::INTERNAL_ERROR, 'H5登录获取wx用户接口异常');
        }

        //过渡开关打开
        if((bool)$this->settings->get('is_need_transition') && empty($sessionToken)) {
            $this->transitionLoginLogicVoid($wxuser);
        }

        $this->db->beginTransaction();
        try {
            /** @var UserWechat $wechatUser */
            $wechatUser = UserWechat::query()
                ->where('mp_openid', $wxuser->getId())
                ->orWhere('unionid', Arr::get($wxuser->getRaw(), 'unionid'))
                ->lockForUpdate()
                ->first();

            if (!$wechatUser || !$wechatUser->user) {
                // 更新微信用户信息
                if (!$wechatUser) {
                    $wechatUser = new UserWechat();
                }
                $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), $actor));

                // 自动注册
                if ($actor->isGuest()) {
                    // 站点关闭注册
                    if (!(bool)$this->settings->get('register_close')) {
                        $this->db->rollBack();
                        $this->outPut(ResponseCode::REGISTER_CLOSE);
                    }

                    $data['code']               = $inviteCode;
                    $data['username']           = Str::of($wechatUser->nickname)->substr(0, 15);
                    $data['register_reason']    = trans('user.register_by_wechat_h5');
                    $user = $this->bus->dispatch(
                        new AutoRegisterUser($this->request->getAttribute('actor'), $data)
                    );
                    $wechatUser->user_id = $user->id;
                    // 先设置关系，为了同步微信头像
                    $wechatUser->setRelation('user', $user);
                    $wechatUser->save();
                    $this->updateUserBindType($user,AuthUtils::WECHAT);
                    $this->db->commit();

                } else {
                    if (!$actor->isGuest() && is_null($actor->wechat)) {
                        // 登陆用户且没有绑定||换绑微信 添加微信绑定关系
                        $wechatUser->user_id = $actor->id;
                        $wechatUser->setRelation('user', $actor);
                        $wechatUser->save();
                        $this->updateUserBindType($actor,AuthUtils::WECHAT);
                        $this->db->commit();
                    }
                }
            } else {
                // 登陆用户和微信绑定不同时，微信已绑定用户，抛出异常
                if (!$actor->isGuest() && $actor->id != $wechatUser->user_id) {
                    $this->db->rollBack();
                    $this->outPut(ResponseCode::ACCOUNT_HAS_BEEN_BOUND);
                }

                // 登陆用户和微信绑定相同，更新微信信息
                $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), $wechatUser->user));
                $wechatUser->save();
                $this->db->commit();
            }

            if (empty($wechatUser) || empty($wechatUser->user)) {
                app('errorLog')->info('requestId：' . $this->requestId . '-' . '$wechatUser或者$wechatUser->user用户不能为空-WechatH5LoginController');
                $this->outPut(ResponseCode::INVALID_PARAMETER,'微信用户不能为空');
            }

            if (empty($wechatUser->user->username)) {
                app('errorLog')->info('requestId：' . $this->requestId . '-' . '$wechatUser->user->username不能为空-WechatH5LoginController');
                $this->outPut(ResponseCode::USERNAME_NOT_NULL,'微信所绑定的用户名为空');
            }
            // 创建 token
            $params = [
                'username' => $wechatUser->user->username,
                'password' => ''
            ];

            $data = $this->fixData($wxuser->getRaw(), $actor);
            unset($data['user_id']);
            $wechatUser->setRawAttributes($data);
            $wechatUser->save();
            $this->db->commit();
            GenJwtToken::setUid($wechatUser->user->id);
            $response = $this->bus->dispatch(
                new GenJwtToken($params)
            );

            //微信扫码登录，待审核状态
            if ($response->getStatusCode() === 200) {
                if($wechatUser->user->status != User::STATUS_MOD){
                    $this->events->dispatch(new Logind($wechatUser->user));
                }
            }

            $accessToken = json_decode($response->getBody());

            // bound
            if ($sessionToken) {
                if (empty($accessToken)) {
                    return $this->outPut(ResponseCode::PC_QRCODE_TIME_FAIL);
                }

                $accessToken = $this->bound->pcLogin($sessionToken, (array)$accessToken, ['user_id' => $wechatUser->user->id]);

                $this->updateUserBindType($wechatUser->user,AuthUtils::WECHAT);
            }

            $result = $this->camelData(collect($accessToken));

            $result = $this->addUserInfo($wechatUser->user, $result);

            $this->outPut(ResponseCode::SUCCESS, '', $result);

        } catch (Exception $e) {
            app('errorLog')->info('requestId：' . $this->requestId . '-' . 'H5登录接口异常-WechatH5LoginController： 入参：'
                                  .'mp_openid:' . $wxuser->getId() .'inviteCode:'.$this->inPut('inviteCode')
                                  .'sessionToken:'.$this->inPut('sessionToken')
                                  .'userId:'.$this->user->id . ';异常：' . $e->getMessage());
            $this->db->rollBack();
            return $this->outPut(ResponseCode::INTERNAL_ERROR, 'H5登录接口异常');
        }
//        $this->error($wxuser, $actor, $wechatUser, null, $sessionToken);
    }

    /**
     * @param $wxuser
     * @param $actor
     * @param UserWechat $wechatUser
     * @param null $rebind 换绑时返回新的code供前端使用
     * @param null $sessionToken
     * @return mixed
     * @throws NoUserException
     */
    private function error($wxuser, $actor, $wechatUser, $rebind = null, $sessionToken = null)
    {
        $rawUser = $wxuser->getRaw();

        if (!$wechatUser) {
            $wechatUser = new UserWechat();
        }
        $wechatUser->setRawAttributes($this->fixData($rawUser, $actor));
        $wechatUser->save();
        $this->db->commit();
        if ($actor->id) {
            $this->outPut(ResponseCode::SUCCESS, '', $this->camelData($actor));
        }

        $token = SessionToken::generate('wechat', $rawUser);
        $token->save();

        $noUserException = new NoUserException();
        $noUserException->setToken($token);
        $noUserException->setUser(Arr::only($wechatUser->toArray(), ['nickname', 'headimgurl']));
        $rebind && $noUserException->setCode('rebind_mp_wechat');

        // 存储异常 PC 端使用
        if (!is_null($sessionToken)) {
            $sessionTokenQuery = SessionToken::query()->where('token', $sessionToken)->first();
            if (!empty($sessionTokenQuery)) {
                /** @var SessionToken $sessionTokenQuery */
                $sessionTokenQuery->payload = [
                    'token' => $token,
                    'code' => $noUserException->getCode() ?: 'no_bind_user',
                    'user' => $noUserException->getUser(),
                    'rebind' => $rebind,
                ];
                $sessionTokenQuery->save();
            }
        }

        $this->outPut(ResponseCode::NET_ERROR);
    }


    /**
     * 过渡阶段微信登录，过渡开关打开走此流程
     * @param $wxuser  //授权后微信信息
     */
    private function transitionLoginLogicVoid($wxuser)
    {
        $this->db->beginTransaction();
        try {
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
            $userWechatId = $wechatUser ? $wechatUser->id : 0;
            if(is_null($wechatUser->user)) {
                // 站点关闭注册
                if (!(bool)$this->settings->get('register_close')) {
                    $this->db->rollBack();
                    $this->outPut(ResponseCode::REGISTER_CLOSE);
                }
                $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), new User()));
                $wechatUser->save();//微信信息写入user_wechats
                $userWechatId = $wechatUser->id ? $wechatUser->id : $userWechatId;

                //生成sessionToken,并把user_wechats 信息写入session_token
                $token = SessionToken::generate(SessionToken::WECHAT_TRANSITION_LOGIN, ['user_wechat_id' => $userWechatId], null, 1800);
                $token->save();
                $sessionToken = $token->token;

                $this->db->commit();
                //把token返回用户绑定用户使用
                $this->outPut(ResponseCode::NEED_BIND_USER_OR_CREATE_USER, '', ['sessionToken' => $sessionToken, 'nickname' => $wechatUser->nickname]);
            }

            // 登陆用户和微信绑定相同，更新微信信息
            $wechatUser->setRawAttributes($this->fixData($wxuser->getRaw(), $wechatUser->user));
            $wechatUser->save();

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

            $this->db->commit();
            $this->outPut(ResponseCode::SUCCESS, '', $result);
        } catch (\Exception $e) {
            app('errorLog')->info('requestId：' . $this->requestId . '-' . 'H5过渡阶段登录异常-WechatH5LoginController： '
                                  .'mp_openid:' . $wxuser->getId() .'inviteCode:'.$this->inPut('inviteCode')
                                  .'sessionToken:'.$this->inPut('sessionToken')
                                  .'userId:'.$this->user->id . ';异常：' . $e->getMessage());
            $this->db->rollBack();
            return $this->outPut(ResponseCode::INTERNAL_ERROR, 'H5过渡阶段登录异常');
        }
    }
}
