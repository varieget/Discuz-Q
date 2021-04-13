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

use App\Api\Serializer\TokenSerializer;
use App\Censor\Censor;
use App\Commands\Users\GenJwtToken;
use App\Commands\Users\RegisterUser;
use App\Common\ResponseCode;
use App\Events\Users\Registered;
use App\Events\Users\RegisteredCheck;
use App\Events\Users\Saving;
use App\Models\Invite;
use App\Models\SessionToken;
use App\Models\User;
use App\Notifications\Messages\Wechat\RegisterWechatMessage;
use App\Notifications\System;
use App\Repositories\UserRepository;
use App\User\Bind;
use App\Validators\UserValidator;
use Carbon\Carbon;
use Discuz\Api\Controller\AbstractCreateController;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Auth\Exception\RegisterException;
use Discuz\Base\DzqController;
use Discuz\Contracts\Setting\SettingsRepository;
use Discuz\Foundation\Application;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class SmsRegisterController extends DzqController
{
    use AssertPermissionTrait;

    protected $bus;

    protected $users;

    protected $settings;

    protected $app;

    protected $bind;

    protected $events;
    /**
     * @var Censor
     */
    private $censor;
    /**
     * @var UserValidator
     */
    private $validator;

    public function __construct(
        Dispatcher $bus,
        UserRepository $users,
        SettingsRepository $settings,
        Application $app,
        Bind $bind,
        Events $events,
        Censor $censor,
        UserValidator $validator
    ){
        $this->bus = $bus;
        $this->users = $users;
        $this->settings = $settings;
        $this->app = $app;
        $this->bind = $bind;
        $this->events = $events;
        $this->censor = $censor;
        $this->validator = $validator;
    }

    public function main()
    {
        if (!(bool)$this->settings->get('register_close')) {
            $this->outPut(ResponseCode::REGISTER_CLOSE, ResponseCode::$codeMap[ResponseCode::REGISTER_CLOSE]);
        }

        $type = $this->inPut('type');
        $username = $this->inPut('username');
        $password = $this->inPut('password');
        $mobile = $this->inPut('mobile');
        $register_reason = $this->inPut('register_reason');
        $captcha_ticket = $this->inPut('captcha_ticket');
        $captcha_rand_str = $this->inPut('captcha_rand_str');
        $mobileToken = $this->inPut('mobileToken');

        $ip = ip($this->request->getServerParams());
        $port = Arr::get($this->request->getServerParams(), 'REMOTE_PORT', 0);

        $data = array();
        $data['username'] = $username;
        $data['password'] = $password;
        $data['register_ip'] = $ip;
        $data['register_port'] = $port;
        $data['register_reason'] = $register_reason;

        //新增参数，注册类型 0:用户名模式 1:手机号模式 2:微信无感登录模式
        $registerType = $this->settings->get('register_type');

        //若参数与配置不为手机号注册，抛异常不再执行
        if($type != 'register' || $registerType != 1) {
            $this->outPut(ResponseCode::REGISTER_TYPE_ERROR,
                                ResponseCode::$codeMap[ResponseCode::REGISTER_TYPE_ERROR]
            );
        }

        // check invite code
        if (!empty($code)) {
            if (Invite::lengthByAdmin($code)) {
                if (!$exists = Invite::query()->where('code', $code)->exists()) {
                    $this->outPut(ResponseCode::NET_ERROR, trans('user.register_decrypt_code_failed'));
                }
            } else {
                if (!$exists = User::query()->find($code)->exists()) {
                    $this->outPut(ResponseCode::NET_ERROR, trans('user.register_decrypt_code_failed'));
                }
            }
        }

        //校验当前手机号是否已注册
        if ($mobileExists = User::query()->where('mobile', $mobile)->exists()) {
            $this->outPut(ResponseCode::NET_ERROR, ResponseCode::$codeMap[ResponseCode::REGISTER_TYPE_ERROR]);
        }

        // 敏感词校验
        $this->censor->checkText($username, 'username');

        // 注册原因
//        if ($this->settings->get('register_validate', 'default', false)) {
//            if (!Arr::has($this->data, 'register_reason')) {
//                throw new TranslatorException('setting_fill_register_reason');
//            }
//        }

        $user = User::register(Arr::only($data, ['username', 'password', 'register_ip', 'register_port', 'register_reason']));
        // 注册验证码(无感模式不走验证码，开启也不走)
        $captcha = '';  // 默认为空将不走验证
        if ((bool)$this->settings->get('register_captcha') &&
            (bool)$this->settings->get('qcloud_captcha', 'qcloud') &&
            ($this->settings->get('register_type', 'default') != 2)) {
            $captcha = [
                $captcha_ticket,
                $captcha_rand_str,
                $ip,
            ];
        }

        // 付费模式，默认注册时即到期
        if ($this->settings->get('site_mode') == 'pay') {
            $user->expired_at = Carbon::now();
        }
        // 审核模式，设置注册为审核状态
        if ($this->settings->get('register_validate') || $this->censor->isMod) {
            $user->status = 2;
        }

//        $this->events->dispatch(
//            new Saving($user, $this->user, $data)
//        );

        // 密码为空的时候，不验证密码，允许创建密码为空的用户(但无法登录，只能用其它方法登录)
        $attrs_to_validate = array_merge($user->getAttributes(), compact('password', 'password_confirmation', 'captcha'));
        if ($password === '') {
            unset($attrs_to_validate['password']);
        }
        unset($attrs_to_validate['register_reason']);
        $this->validator->valid($attrs_to_validate);

        $user->save();
        $user->raise(new Registered($user, $this->user, $data));

//        $this->dispatchEventsFor($user, $this->user);

//        return $user;


//        $rebind = Arr::get($attributes, 'rebind', 0);
        //绑定公众号
//        if ($token) {
//            $this->bind->withToken($token, $user, $rebind);
//            // 判断是否开启了注册审核
//            if (!(bool)$this->settings->get('register_validate')) {
//                // Tag 发送通知 (在注册绑定微信后 发送注册微信通知)
//                $user->notify(new System(RegisterWechatMessage::class, $user, ['send_type' => 'wechat']));
//            }
//        }

        //绑定小程序信息
//        if ($js_code && $iv  && $encryptedData) {
//            $this->bind->bindMiniprogram($js_code, $iv, $encryptedData, $rebind, $user);
//        }

        //绑定手机号
         if ($mobileToken) {
             $this->bind->mobile($mobileToken, $user);
         }

        // 注册后的登录检查
        if (!(bool)$this->settings->get('register_validate')) {
            $this->events->dispatch(new RegisteredCheck($user));
        }
        $response = $this->bus->dispatch(
            new GenJwtToken($username)
        );
        return json_decode($response->getBody());
    }
}
