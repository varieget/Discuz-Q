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

namespace App\Api\Controller\SettingsV3;

use App\Common\ResponseCode;
use Discuz\Base\DzqLog;
use Discuz\Common\Utils;
use Discuz\Contracts\Setting\SettingsRepository;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Dnspod\V20210323\DnspodClient;
use TencentCloud\Dnspod\V20210323\Models\CreateDomainAliasRequest;
use TencentCloud\Dnspod\V20210323\Models\CreateDomainRequest;
use TencentCloud\Dnspod\V20210323\Models\CreateRecordRequest;

trait DnspodTrait
{
    /**
     * @var SettingsRepository
     */
    public $settings;

    public $client;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    protected function initDnspodClient(): DnspodClient
    {
        // https://console.cloud.tencent.com/api/explorer?Product=dnspod&Version=2021-03-23&Action=CreateRecord&SignVersion=
        $secretId = $this->settings->get('qcloud_secret_id', 'qcloud');
        $secretKey = $this->settings->get('qcloud_secret_key', 'qcloud');

        $cred = new Credential($secretId, $secretKey);
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint('dnspod.tencentcloudapi.com');

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $this->client = new DnspodClient($cred, '', $clientProfile);
        return $this->client;
    }

    protected function commonDnspodDomain($type, $params, $errorMsg)
    {
        try {
            $this->initDnspodClient();

            switch ($type) {
//                case 'createDomainAlias':
//                    $req = new CreateDomainAliasRequest();
//                    $action = 'CreateDomainAlias';
//                    break;
                case 'create':
                    $req = new CreateDomainRequest();
                    $action = 'CreateDomain';
                    break;
                case 'createRecord':
                    $req = new CreateRecordRequest();
                    $action = 'CreateRecord';
                    break;
                default:
                    $req = new CreateRecordRequest();
                    $action = 'CreateRecord';
                    break;
            }

            $req->fromJsonString(json_encode($params));

            $resp = $this->client->$action($req);

            return json_decode($resp->toJsonString(), true);
        } catch (TencentCloudSDKException $e) {
            $errorData = ['errorCode' => $e->getErrorCode(), 'errorMsg' => $e->getMessage()];
            DzqLog::error('dnspodtrait_api_error', $errorData);
            Utils::outPut(ResponseCode::EXTERNAL_API_ERROR, $errorMsg, $errorData);
        }
    }

//    public function createDomainAlias($domainAlias = '', $domain = '')
//    {
//        return $this->commonDnspodDomain('createDomainAlias', [
//            'DomainAlias' => $domainAlias, //域名别名
//            'Domain' => $domain // 主域名
//        ], '创建域名别名错误');
//    }

    public function createDomain($domain = '')
    {
        return $this->commonDnspodDomain('create', [
            'Domain' => $domain // 主域名
        ], '添加域名错误');
    }

    public function createRecord($domain = '', $value = '', $recordType = 'CNAME', $recordLine = '默认', $subDomain = 'www')
    {
        return $this->commonDnspodDomain('update', [
            'Domain' => $domain, // 域名
            'RecordType' => $recordType, // 记录类型，通过 API 记录类型获得，大写英文，比如：A 。
            'RecordLine' => $recordLine, // 记录线路，通过 API 记录线路获得，中文，比如：默认。
            'Value' => $value, // 记录值，如 IP : 200.200.200.200， CNAME : cname.dnspod.com.， MX : mail.dnspod.com.。
            'SubDomain' => $subDomain // 主机记录，如 www，如果不传，默认为 @。
        ], '添加域名解析记录错误');
    }
}
