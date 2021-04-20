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

use App\Common\AuthUtils;
use App\Common\ResponseCode;
use App\Models\MobileCode;
use App\Models\SessionToken;
use App\Models\User;
use App\Repositories\MobileCodeRepository;
use Discuz\Base\DzqController;
use Illuminate\Support\Carbon;

abstract class AuthBaseController extends DzqController
{
    /**
     * 获取扫码后token登录信息数据
     * @return SessionToken
     */
    public function getScanCodeToken()
    {
        $sessionToken = $this->inPut('session_token');
        $token = SessionToken::get($sessionToken);
        if (empty($token)) {
            // 二维码已失效，扫码超时
            $this->outPut(ResponseCode::PC_QRCODE_TIME_OUT, ResponseCode::$codeMap[ResponseCode::PC_QRCODE_TIME_OUT]);
        }

        if (is_null($token->payload)) {
            // 扫码中
            $this->outPut(ResponseCode::PC_QRCODE_SCANNING_CODE, ResponseCode::$codeMap[ResponseCode::PC_QRCODE_SCANNING_CODE]);
        }

        return $token;
    }

    protected function getWxuser()
    {
        $code           = $this->inPut('code');
        $sessionId      = $this->inPut('sessionId');

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

        $driver = $this->socialite->driver('wechat');
        $wxuser = $driver->user();

        return $wxuser;
    }

    public function recordWechatLog($wechatUser)
    {
        $wechatlog = app('wechatLog');
        $wechatlog->info('wechat_info', [
            'wechat_user'   => $wechatUser          == null ? '' : $wechatUser->toArray(),
            'user_info'     => $wechatUser->user    == null ? '' : $wechatUser->user->toArray()
        ]);
    }

    public function fixData($rawUser, $actor)
    {
        $data = array_merge($rawUser, [
                                        'user_id'   => $actor->id ?: null,
                                        'mp_openid' => $rawUser['openid']]
        );
        unset($data['openid'], $data['language']);
        $data['privilege'] = serialize($data['privilege']);

        return $data;
    }

    public function getMobileCode($type): MobileCode
    {
        $mobile = $this->inPut('mobile');
        $code   = $this->inPut('code');

        $this->dzqValidate([
            'mobile'    => $mobile,
            'code'      => $code
        ], [
            'mobile'    => 'required',
            'code'      => 'required'
        ]);

        $mobileCode = $this->changeMobileCodeState($mobile, $type, $code);

        return $mobileCode;
    }

    /**
     * 修改手机验证码的状态
     * @param $mobile
     * @param $type
     * @param $code
     * @return MobileCode
     */
    public function changeMobileCodeState($mobile, $type, $code)
    {
        $mobileCodeRepository = app(MobileCodeRepository::class);
        /**
         * @var MobileCode $mobileCode
         **/
        $mobileCode = $mobileCodeRepository->getSmsCode($mobile, $type);

        if (!$mobileCode || $mobileCode->code !== $code || $mobileCode->expired_at < Carbon::now()) {
            $this->outPut(ResponseCode::NET_ERROR, ResponseCode::$codeMap[ResponseCode::NET_ERROR]);
        }

        $mobileCode->changeState(MobileCode::USED_STATE);
        $mobileCode->save();

        return $mobileCode;
    }

    public function getWechatMiniProgramParam()
    {
        $data = [
            'jsCode'            => $this->inPut('jsCode'),
            'iv'                => $this->inPut('iv'),
            'encryptedData'     => $this->inPut('encryptedData')
        ];
        $this->dzqValidate($data, [
            'jsCode'            => 'required',
            'iv'                => 'required',
            'encryptedData'     => 'required'
       ]);

        return $data;
    }

    public function updateUserBindType($user,$bindType){
        if (!in_array($bindType,AuthUtils::getLoginTypeArr())) {
            $this->outPut(ResponseCode::BIND_TYPE_IS_NULL,
                          ResponseCode::$codeMap[ResponseCode::BIND_TYPE_IS_NULL]
            );
        }
        $userBindType = empty($user->bind_type) ? 0 :$user->bind_type;
        $existBindType = AuthUtils::getBindTypeArrByCombinationBindType($userBindType);

        if (!in_array($bindType, $existBindType)) {
            array_push($existBindType, $bindType);
            $newBindType  = AuthUtils::getBindType($existBindType);
            if (is_object($user)) {
                $user->bind_type = $newBindType;
                $user->save();
            } else {
                $this->outPut(ResponseCode::PARAM_IS_NOT_OBJECT,
                              ResponseCode::$codeMap[ResponseCode::PARAM_IS_NOT_OBJECT]
                );
            }
        }
    }

    public function addUserInfo($user, $result) {
        if (empty($user['nickname'])) {
            $result['isMissNickname'] = true;
        } else {
            $result['isMissNickname'] = false;
        }

        $result['userStatus'] = $user->status ?: 0;

        $result['uid'] = $user->id ?: 0;

        return $result;
    }

}
