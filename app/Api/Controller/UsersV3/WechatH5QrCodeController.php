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
use Endroid\QrCode\QrCode;
use Illuminate\Contracts\Routing\UrlGenerator;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Wechat\EasyWechatTrait;

class WechatH5QrCodeController extends DzqController
{

    use EasyWechatTrait;
    use AssertPermissionTrait;


    public $optionalInclude = [];

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * 二维码生成类型
     * @var string[]
     */
    static $qrcodeType = [
        'pc_login',
        'pc_bind',
        'mobile_browser_login',
        'mobile_browser_bind'
    ];

    /**
     * 二维码生成类型与token标识映射
     * @var array
     */
    static $qrcodeTypeAndIdentifierMap = [
        'pc_login'              => SessionToken::WECHAT_PC_LOGIN,
        'pc_bind'               => SessionToken::WECHAT_PC_BIND,
        'mobile_browser_login'  => SessionToken::WECHAT_MOBILE_LOGIN,
        'mobile_browser_bind'   => SessionToken::WECHAT_MOBILE_BIND
    ];

    /**
     * @param UrlGenerator $url
     */
    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    public function main()
    {
        $type = $this->inPut('type');
        if(! in_array($type, self::$qrcodeType)) {
            $this->outPut(ResponseCode::GEN_QRCODE_TYPE_ERROR, ResponseCode::$codeMap[ResponseCode::GEN_QRCODE_TYPE_ERROR]);
        }
        $redirectUri = urldecode($this->inPut('redirectUri'));

        //分离出参数
        $conData = $this->parseUrlQuery($redirectUri);
        //回调页面url
        $redirectUri = $conData['url'];
        //参数
        $query = $conData['params'];
        //手机浏览器绑定则由前端传session_token
        $sessionToken = $this->inPut('sessionToken');
        if($type == 'mobile_browser_bind' && ! $sessionToken) {
            $this->outPut(ResponseCode::GEN_QRCODE_TYPE_ERROR, ResponseCode::$codeMap[ResponseCode::GEN_QRCODE_TYPE_ERROR]);
        }
        if(!empty($sessionToken)) {
            $query = array_merge($query, ['sessionToken' => $sessionToken]);
        }


        if($type != 'mobile_browser_bind') {
            //跳转路由选择
            $actor = $this->user;
            if($actor && $actor->id) {
                $token = SessionToken::generate(self::$qrcodeTypeAndIdentifierMap[$type], null, $actor->id);
            } else {
                $token = SessionToken::generate(self::$qrcodeTypeAndIdentifierMap[$type]);
            }
            // create token
            $token->save();

            $sessionToken = $token->token;
        }

        $locationUrl = $this->url->action('/apiv3/users/wechat/h5.oauth?redirect='.$redirectUri, $query);
        $locationUrlArr = explode('redirect=', $locationUrl);
        $locationUrl = $locationUrlArr[0].urlencode($locationUrlArr[1]);

        //去掉无参数时最后一个是 ? 的字符
        $locationUrl = rtrim($locationUrl, "?");
        $qrCode = new QrCode($locationUrl);

        $binary = $qrCode->writeString();

        $baseImg = 'data:image/png;base64,' . base64_encode($binary);

        $data = [
            'sessionToken' => $sessionToken,
            'base64Img' => $baseImg,
        ];
        if($type=='mobile_browser_login') {
            unset($data['sessionToken']);
        }

        $this->outPut(ResponseCode::SUCCESS, '', $data);
    }

    /**
     *
     * 从url 中分离出uri与参数
     * @param $url
     * @return mixed
     */
    protected function parseUrlQuery($url)
    {
        $urlParse = explode('?', $url);
        $data['url'] = $urlParse[0];
        $data['params'] = [];
        if(isset($urlParse[1]) && !empty($urlParse[1])) {
            $queryParts = explode('&', $urlParse[1]);
            $params = array();
            foreach ($queryParts as $param) {
                $item = explode('=', $param);
                $params[$item[0]] = $item[1];
            }
            $data['params'] = $params;
        }
        return $data;
    }

}
