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
use App\Settings\SettingsRepository;
use App\User\Bind;
use App\User\Bound;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Guest;
use Discuz\Base\DzqController;
use Discuz\Wechat\EasyWechatTrait;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\ConnectionInterface;

class WechatMiniProgramBindController extends AuthBaseController
{
    use AssertPermissionTrait;
    use EasyWechatTrait;

    protected $bus;
    protected $cache;
    protected $validation;
    protected $events;
    protected $settings;
    protected $bind;
    protected $db;
    protected $bound;

    public function __construct(
        Dispatcher          $bus,
        Repository          $cache,
        ValidationFactory   $validation,
        Events              $events,
        SettingsRepository  $settings,
        Bind                $bind,
        ConnectionInterface $db,
        Bound               $bound
    ){
        $this->bus          = $bus;
        $this->cache        = $cache;
        $this->validation   = $validation;
        $this->events       = $events;
        $this->settings     = $settings;
        $this->bind         = $bind;
        $this->db           = $db;
        $this->bound        = $bound;
    }

    public function main()
    {
        $actor          = $this->user;
        $user           = !$actor->isGuest() ? $actor : new Guest();
        $js_code        = $this->inPut('js_code');
        $iv             = $this->inPut('iv');
        $encryptedData  = $this->inPut('encryptedData');
        $code           = $this->inPut('code');
        $sessionToken   = $this->inPut('session_token');
        $rebind         = 0;
        $register       = 1;

        $data = [   'js_code'       => $js_code,
                    'iv'            => $iv,
                    'encryptedData' => $encryptedData
        ];
        $this->validation->make($data,[  'js_code'       => 'required',
                                         'iv'            => 'required',
                                         'encryptedData' => 'required'
                                     ]
        )->validate();

        // 绑定小程序
        $this->db->beginTransaction();
        try {
            $wechatUser = $this->bind->bindMiniprogram($js_code, $iv, $encryptedData, $rebind, $user, true);
        } catch (Exception $e) {
            $this->db->rollback();
            $this->outPut(ResponseCode::NET_ERROR,
                                ResponseCode::$codeMap[ResponseCode::NET_ERROR] ,
                                $e->getMessage()
            );
        }

        if ($wechatUser->user_id) {
                $this->db->rollback();
                $this->outPut(ResponseCode::BIND_ERROR, ResponseCode::$codeMap[ResponseCode::BIND_ERROR]);
        } else {
                $wechatUser->user_id = $user->id;
                // 先设置关系再save，为了同步微信头像
                $wechatUser->setRelation('user', $user);
                $wechatUser->save();

                $this->db->commit();

                // PC扫码使用
                if ($sessionToken) {
                    $accessToken = $this->bound->pcMiniProgramBind($sessionToken, '', ['user_id' => $wechatUser->user->id]);
                }
        }
        $this->outPut(ResponseCode::SUCCESS, '', $actor);
    }
}
