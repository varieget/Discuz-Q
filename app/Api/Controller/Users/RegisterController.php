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

use App\Api\Serializer\TokenSerializer;
use App\Commands\Users\GenJwtToken;
use App\Commands\Users\RegisterUser;
use App\Events\Users\RegisteredCheck;
use App\Models\SessionToken;
use App\Notifications\Messages\Wechat\RegisterWechatMessage;
use App\Notifications\System;
use App\Repositories\UserRepository;
use App\User\Bind;
use Discuz\Api\Controller\AbstractCreateController;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Auth\Exception\RegisterException;
use Discuz\Contracts\Setting\SettingsRepository;
use Discuz\Foundation\Application;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class RegisterController extends AbstractCreateController
{
    use AssertPermissionTrait;

    protected $bus;

    protected $users;

    protected $settings;

    protected $app;

    protected $bind;

    protected $events;

    public function __construct(Dispatcher $bus, UserRepository $users, SettingsRepository $settings, Application $app, Bind $bind, Events $events)
    {
        $this->bus = $bus;
        $this->users = $users;
        $this->settings = $settings;
        $this->app = $app;
        $this->bind = $bind;
        $this->events = $events;
    }

    public $serializer = TokenSerializer::class;

    /**
     * {@inheritdoc}
     * @throws PermissionDeniedException
     * @throws \Exception
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        if (!(bool)$this->settings->get('register_close')) {
            throw new PermissionDeniedException('register_close');
        }
        $attributes = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $attributes['register_ip'] = ip($request->getServerParams());
        $attributes['register_port'] = Arr::get($request->getServerParams(), 'REMOTE_PORT', 0);
        $type = intval(Arr::get($attributes, 'register_type'));
        //新增参数，注册类型
        $registerType = $this->settings->get('register_type');

        //若参数与配置不一致，抛异常不再执行
        if($type != $registerType) {
            throw new RegisterException('Register Type Error');
        }

        /**手机号模式下,或无感模式下只有微信可用**/
        /**公众号使用**/
        $token = Arr::get($attributes, 'token');
        /**小程序使用**/
        $js_code = Arr::get($attributes, 'js_code');
        $noAES = Arr::get($attributes, 'noAES');
//        $iv = Arr::get($attributes, 'iv');
//        $encryptedData = Arr::get($attributes, 'encryptedData');

        //手机号或者无感模式下，若公众号或小程序的参数都不存在，则不可使用该接口
        if($registerType == 1 || $registerType == 2) {
            if(empty($token) && empty($js_code) && empty($noAES)) {
                throw new RegisterException('Register Method Error');
            }
            if(! empty($token)) {
                //校验token
                if(empty(SessionToken::get($token))) {
                    throw new RegisterException('Register Token Error');
                }
            }
            /**小程序使用，三个参数必须都存在才可以使用**/
            /*if((! empty($js_code) && (empty($iv) || empty($encryptedData)))  ||
                (! empty($iv) && (empty($js_code) || empty($encryptedData))) ||
                (! empty($encryptedData) && (empty($js_code) || empty($iv)))
            ) {
                throw new RegisterException('Register Mini Token Error');
            }*/
            if(! empty($js_code) || empty($noAES)) {
                throw new RegisterException('Register Mini Token Error');
            }
        }

        $user = $this->bus->dispatch(
            new RegisterUser($request->getAttribute('actor'), $attributes)
        );

        $rebind = Arr::get($attributes, 'rebind', 0);
        //绑定公众号
        if ($token) {
            $this->bind->withToken($token, $user, $rebind);
            // 判断是否开启了注册审核
            if (!(bool)$this->settings->get('register_validate')) {
                // Tag 发送通知 (在注册绑定微信后 发送注册微信通知)
                $user->notify(new System(RegisterWechatMessage::class, $user, ['send_type' => 'wechat']));
            }
        }
        //绑定小程序信息
        if ($js_code && $noAES) {
            $this->bind->bindMiniprogramByCode($js_code,  $user);
        }

        //绑定手机号
        /* if ($mobileToken = Arr::get($attributes, 'mobileToken')) {
             $this->bind->mobile($mobileToken, $user);
         }*/

        // 注册后的登录检查
        if (!(bool)$this->settings->get('register_validate')) {
            $this->events->dispatch(new RegisteredCheck($user));
        }
        $response = $this->bus->dispatch(
            new GenJwtToken(Arr::only($attributes, 'username'))
        );
        return json_decode($response->getBody());
    }
}
