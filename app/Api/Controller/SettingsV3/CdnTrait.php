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
use TencentCloud\Cdn\V20180606\Models\AddCdnDomainRequest;
use TencentCloud\Cdn\V20180606\Models\DeleteCdnDomainRequest;
use TencentCloud\Cdn\V20180606\Models\DescribeDomainsRequest;
use TencentCloud\Cdn\V20180606\Models\StartCdnDomainRequest;
use TencentCloud\Cdn\V20180606\Models\StopCdnDomainRequest;
use TencentCloud\Cdn\V20180606\Models\UpdateDomainConfigRequest;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Cdn\V20180606\CdnClient;

trait CdnTrait
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

    protected function initCdnClient(): CdnClient
    {
        // https://console.cloud.tencent.com/api/explorer?Product=cdn&Version=2018-06-06&Action=AddCdnDomain&SignVersion=
        $secretId = $this->settings->get('qcloud_secret_id', 'qcloud');
        $secretKey = $this->settings->get('qcloud_secret_key', 'qcloud');

        $cred = new Credential($secretId, $secretKey);
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint('cdn.tencentcloudapi.com');

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $this->client = new CdnClient($cred, '', $clientProfile);
        return $this->client;
    }

    protected function commonCdnDomain($type, $params, $errorMsg)
    {
        try {
            $this->initCdnClient();

            switch ($type) {
                case 'add':
                    $req = new AddCdnDomainRequest();
                    $action = 'AddCdnDomain';
                    break;
                case 'update':
                    $req = new UpdateDomainConfigRequest();
                    $action = 'UpdateDomainConfig';
                    break;
                case 'delete':
                    $req = new DeleteCdnDomainRequest();
                    $action = 'DeleteCdnDomain';
                    break;
                case 'start':
                    $req = new StartCdnDomainRequest();
                    $action = 'StartCdnDomain';
                    break;
                case 'stop':
                    $req = new StopCdnDomainRequest();
                    $action = 'StopCdnDomain';
                    break;
                case 'describe':
                    $req = new DescribeDomainsRequest();
                    $action = 'DescribeDomains';
                    break;
                default:
                    $req = new UpdateDomainConfigRequest();
                    $action = 'UpdateDomainConfig';
                    break;
            }

            $req->fromJsonString(json_encode($params));

            $resp = $this->client->$action($req);

            return json_decode($resp->toJsonString(), true);
        } catch (TencentCloudSDKException $e) {
            $errorData = ['errorCode' => $e->getErrorCode(), 'errorMsg' => $e->getMessage()];
            DzqLog::error('cdntrait_api_error', $errorData);
            Utils::outPut(ResponseCode::EXTERNAL_API_ERROR, $errorMsg, $errorData);
        }
    }

    public function addCdnDomain(string $domain, array $origins, string $serverName)
    {
        return $this->commonCdnDomain('add', [
            'Domain' => $domain, // 加速域名
            'ServiceType' => 'web', // 加速域名业务类型 web：静态加速 download：下载加速 media：流媒体点播加速
            'Origin' => [
                'Origins' => $origins,// 源站地址
                'OriginType' => 'ip',
                'ServerName' => $serverName // 回源HOST
            ],
        ], '新增腾讯云CDN配置错误');
    }

    public function updateCdnDomain(string $domain, array $origins, string $serverName, $serviceType = 'web', $originType = 'ip')
    {
        return $this->commonCdnDomain('update', [
            'Domain' => $domain, // 加速域名
            'ServiceType' => $serviceType, // 加速域名业务类型 web：静态加速 download：下载加速 media：流媒体点播加速
            'Origin' => [
                'Origins' => $origins,// 源站地址
                'OriginType' => $originType,
                'ServerName' => $serverName // 回源HOST
            ],
        ], '修改腾讯云CDN配置错误');
    }

    public function deleteCdnDomain($domain)
    {
        return $this->commonCdnDomain('delete', ['Domain' => $domain], '删除加速域名错误');
    }

    public function startCdnDomain($domain)
    {
        return $this->commonCdnDomain('start', ['Domain' => $domain], '开启加速域名错误');
    }

    public function stopCdnDomain($domain)
    {
        return $this->commonCdnDomain('stop', ['Domain' => $domain,], '停用加速域名错误');
    }

    public function describeDomains($value)
    {
        return $this->commonCdnDomain('describe', [
            'Name' => 'domain',
            'Value' => [$value],
        ], '查询域名基本信息错误');
    }

}
