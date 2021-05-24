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
use App\Models\MobileCode;
use App\Models\SessionToken;
use App\Models\UserWechat;
use App\Repositories\MobileCodeRepository;
use Discuz\Base\DzqController;
use Discuz\Socialite\Exception\SocialiteException;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

abstract class AuthBaseController extends DzqController
{
    /**
     * 获取扫码后token登录信息数据
     * @return SessionToken
     */
    public function getScanCodeToken()
    {
        $sessionToken = $this->inPut('sessionToken');
        $token = SessionToken::get($sessionToken);
        if (empty($token)) {
            // 二维码已失效，扫码超时
            $this->outPut(ResponseCode::PC_QRCODE_TIME_OUT);
        }

        if (is_null($token->payload)) {
            // 扫码中
            $this->outPut(ResponseCode::PC_QRCODE_SCANNING_CODE);
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
            $this->outPut(ResponseCode::SMS_CODE_ERROR);
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
            $this->outPut(ResponseCode::BIND_TYPE_IS_NULL);
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
                $this->outPut(ResponseCode::PARAM_IS_NOT_OBJECT);
            }
        }
    }

    public function addUserInfo($user, $result) {
        if (empty($user['nickname'])) {
            $result['isMissNickname'] = true;
        } else {
            $result['isMissNickname'] = false;
        }

        $result['avatarUrl']    = !empty($user->avatar) ? $user->avatar : '';
        $result['userStatus']   = !empty($user->status) ? $user->status : 0;
        $result['uid']          = !empty($user->id) ? $user->id : 0;

        return $result;
    }

    public function getMiniWechatUser($app, $jsCode, $iv, $encryptedData, $user = null){
//        $wechatUser = UserWechat::query()->where('id', 42)->first();
//        return $wechatUser;
        //获取小程序登陆session key
        $authSession = $app->auth->session($jsCode);
        if (isset($authSession['errcode']) && $authSession['errcode'] != 0) {
            $this->outPut(ResponseCode::NET_ERROR,
                          ResponseCode::$codeMap[ResponseCode::NET_ERROR],
                          ['errmsg' => $authSession['errmsg'], 'errcode' => $authSession['errcode']]);
//            throw new SocialiteException($authSession['errmsg'], $authSession['errcode']);
        }
        $decryptedData = $app->encryptor->decryptData(
            Arr::get($authSession, 'session_key'),
            $iv,
            $encryptedData
        );

//        $unionid        = Arr::get($decryptedData, 'unionId') ?: Arr::get($authSession, 'unionid', '');
//        $openid         = Arr::get($decryptedData, 'openId') ?: Arr::get($authSession, 'openid');

        $unionid = Arr::get($authSession, 'unionid', '');
        $openid  = Arr::get($authSession, 'openid');

        //获取小程序用户信息
        /** @var UserWechat $wechatUser */
        $wechatUser = UserWechat::query()
            ->when($unionid, function ($query, $unionid) {
                return $query->where('unionid', $unionid);
            })
            ->orWhere('min_openid', $openid)
            ->lockForUpdate()
            ->first();

        if (!$wechatUser || !$wechatUser->exists) {
            $wechatUser = UserWechat::build([]);
        }

        //解密获取数据，更新/插入wechatUser
        if (!$wechatUser->user_id) {
            //注册并绑定、登陆并绑定、手机号登陆注册并绑定时设置关联关系
            $wechatUser->user_id = !empty($user->id) ? $user->id : null;
        }
        $wechatUser->unionid    = $unionid;
        $wechatUser->min_openid = $openid;
        $wechatUser->nickname   = $decryptedData['nickName'];
//        $wechatUser->nickname   = 'VinceLee';
        $wechatUser->city       = $decryptedData['city'];
        $wechatUser->province   = $decryptedData['province'];
        $wechatUser->country    = $decryptedData['country'];
        $wechatUser->sex        = $decryptedData['gender'];
        $wechatUser->headimgurl = $decryptedData['avatarUrl'];
        $wechatUser->save();

        return $wechatUser;
    }

    public function getCookie($name = null){
        if (empty($name)) {
            return $this->request->getCookieParams();
        } else {
            $cookies = $this->request->getCookieParams();
            return !empty($cookies[$name]) ? $cookies[$name] : '';
        }
    }

    public function getAccessToken($user){
        $bus = app(Dispatcher::class);
        if (empty($user) || empty($user->username)) {
            $this->outPut(ResponseCode::WECHAT_INVALID_ARGUMENT_EXCEPTION);
        }
        $params = [
            'username' => $user->username,
            'password' => ''
        ];
        GenJwtToken::setUid($user->id);
        $response = $bus->dispatch(
            new GenJwtToken($params)
        );
        return json_decode($response->getBody(),true);
    }

}
