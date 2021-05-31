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
use Endroid\QrCode\QrCode;
use GuzzleHttp\Client;
use Illuminate\Contracts\Routing\UrlGenerator;

class WechatPcRebindQrCodeController extends DzqController
{
    use EasyWechatTrait;

    protected $settingsRepository;
    protected $httpClient;
    protected $accessToken;
    protected $url;

    public function __construct(SettingsRepository $settingsRepository, UrlGenerator $url)
    {
        $this->settingsRepository   = $settingsRepository;
        $this->url                  = $url;
        $this->httpClient           = new Client();
    }

    public function main()
    {
        $actor = $this->user;
        if (empty($actor->id)) {
            $this->outPut(ResponseCode::USER_LOGIN_STATUS_NOT_NULL);
        }

        $miniWechat = (bool)$this->settingsRepository->get('miniprogram_close', 'wx_miniprogram');
        $wechat     = (bool)$this->settingsRepository->get('offiaccount_close', 'wx_offiaccount');
        if (!$miniWechat && !$wechat) {
            $this->outPut(ResponseCode::NONSUPPORT_WECHAT_REBIND);
        }

        $token = SessionToken::generate(SessionToken::WECHAT_PC_REBIND, null, $actor->id);
        $token->save();
        $sessionToken = $token->token;

        if ($miniWechat) {
//        if (true) {
            //获取小程序全局token
            $app = $this->miniProgram();
            $optional['path'] = '/pages/user/pc-login';
            $wxqrcodeResponse = $app->app_code->getUnlimit($sessionToken, $optional);
            if(is_array($wxqrcodeResponse) && isset($wxqrcodeResponse['errcode']) && isset($wxqrcodeResponse['errmsg'])) {
                //todo 日志记录
                $this->outPut(ResponseCode::MINI_PROGRAM_QR_CODE_ERROR);
            }
            //图片二进制转base64
            $data = [
                'sessionToken' => $sessionToken,
                'base64Img' => 'data:image/png;base64,' . base64_encode($wxqrcodeResponse->getBody()->getContents())
            ];
        }

        if ($wechat) {
//        if (false) {
            $redirectUri = urldecode($this->inPut('redirectUri'));
            $conData = $this->parseUrlQuery($redirectUri);
            $redirectUri = $conData['url'];
            $locationUrl = $this->url->action('/apiv3/users/wechat/h5.oauth?redirect='.$redirectUri);

            $qrCode = new QrCode($locationUrl);

            $binary = $qrCode->writeString();

            $data = [
                'sessionToken' => $sessionToken,
                'base64Img' => 'data:image/png;base64,' . base64_encode($binary),
            ];
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