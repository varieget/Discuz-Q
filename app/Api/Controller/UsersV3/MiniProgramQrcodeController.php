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
use Discuz\Base\DzqController;
use App\Settings\SettingsRepository;
use Discuz\Wechat\EasyWechatTrait;
use GuzzleHttp\Client;

class MiniProgramQrcodeController extends DzqController
{
    use EasyWechatTrait;

    /**
     * 二维码生成类型
     * @var string[]
     */
    static $qrcodeType = [
        'pc_login_mini',
        'pc_bind_mini',
    ];
    /**
     * 二维码生成类型与跳转路由的映射
     * @var string[]
     */
    //todo 对接前端时更换路由
    static $qrcodeTypeAndRouteMap = [
        'pc_login_mini'              => '/pages/user/pc-login',
        'pc_bind_mini'               => '/pages/user/pc-relation',
    ];
    /**
     * 二维码生成类型与token标识映射
     * @var array
     */
    static $qrcodeTypeAndIdentifierMap = [
        'pc_login_mini'              => SessionToken::WECHAT_PC_LOGIN,
        'pc_bind_mini'               => SessionToken::WECHAT_PC_BIND
    ];

    protected $settingsRepository;

    protected $httpClient;

    protected $accessToken;

    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
        $this->httpClient = new Client();
    }

    public function main()
    {
        $type = $this->inPut('type');
        if(! in_array($type, self::$qrcodeType)) {
            $this->outPut(ResponseCode::GEN_QRCODE_TYPE_ERROR, ResponseCode::$codeMap[ResponseCode::GEN_QRCODE_TYPE_ERROR]);
        }

        //跳转路由选择
        $path = self::$qrcodeTypeAndRouteMap[$type];
        $actor = $this->user;
        if ($type != 'pc_bind_mini' && empty($actor->id)) {
            $this->outPut(ResponseCode::USER_LOGIN_STATUS_NOT_NULL);
        }
        if($actor && $actor->id) {
            $token = SessionToken::generate(self::$qrcodeTypeAndIdentifierMap[$type], null, $actor->id);
        } else {
            $token = SessionToken::generate(self::$qrcodeTypeAndIdentifierMap[$type]);
        }
        // create token
        $token->save();

        $sessionToken = $token->token;
        //获取小程序全局token
        $app = $this->miniProgram();
        $optional['path'] = $path;
        $wxqrcodeResponse = $app->app_code->getUnlimit($sessionToken, $optional);
        if(is_array($wxqrcodeResponse) && isset($wxqrcodeResponse['errcode']) && isset($wxqrcodeResponse['errmsg'])) {
            //todo 日志记录
            $this->outPut(ResponseCode::MINI_PROGRAM_QR_CODE_ERROR, ResponseCode::$codeMap[ResponseCode::MINI_PROGRAM_QR_CODE_ERROR]);
        }
        //图片二进制转base64
        $data = [
            'sessionToken' => $token->token,
            'base64Img' => 'data:image/png;base64,' . base64_encode($wxqrcodeResponse->getBody()->getContents())
        ];

        $this->outPut(ResponseCode::SUCCESS, '', $data);
    }
}
