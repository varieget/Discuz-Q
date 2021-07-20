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

namespace App\Traits;

use App\Models\ThreadVideo;
use App\Settings\SettingsRepository;
use Carbon\Carbon;
use Discuz\Base\DzqLog;
use Illuminate\Support\Str;
use Vod\VodUploadClient;
use Vod\Model\VodUploadRequest;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Vod\V20180717\VodClient;
use TencentCloud\Vod\V20180717\Models\ProcessMediaRequest;

trait VideoCloudTrait
{
    protected $url = 'vod.tencentcloudapi.com';

    private function videoUpload($mediaUrl){
        if(empty($mediaUrl)){
            DzqLog::error('媒体文件视url不能为空');
            return false;
        }
        if (strpos($mediaUrl, '?') !== false) {
            $media = explode("?",$mediaUrl);
            $media = $media[0];
            $ext = substr($media,strrpos($media,'.')+1);
        } else {
            $ext = substr($mediaUrl,strrpos($mediaUrl,'.')+1);
        }

        $localFlie = Str::random(40).".".$ext;
        $absoluteUrl = storage_path('tmp/').$localFlie;
        $fileData = @file_get_contents($mediaUrl,false, stream_context_create(['ssl'=>['verify_peer'=>false, 'verify_peer_name'=>false]]));
        if(!$fileData){
            DzqLog::error('媒体文件不存在');
            return false;
        }
        $tempFlie = @file_put_contents($absoluteUrl,$fileData);
        if(!$tempFlie){
            DzqLog::error('下载视频失败');
            return false;
        }
        $settingRepo = app(SettingsRepository::class);
        $secretId = $settingRepo->get('qcloud_secret_id', 'qcloud');
        $secretKey = $settingRepo->get('qcloud_secret_key', 'qcloud');
        $region = $settingRepo->get('qcloud_cos_bucket_area','qcloud');

        if(empty($secretId) || empty($secretKey)){
            DzqLog::error('云点播配置不能为空');
            return false;
        }
        if(!file_exists($absoluteUrl)){
            DzqLog::error('本地临时文件不能为空');
            return false;
        }
        $client = new VodUploadClient($secretId, $secretKey);
        $req = new VodUploadRequest();
        $req->MediaFilePath = $absoluteUrl;
        try {
            $rsp = $client->upload($region, $req);
        } catch (\Exception $e) {
            // 处理上传异常
            DzqLog::error('上传视频接口报错', $e->getMessage());
            return false;
        }
        $fileId = $rsp->FileId;
        $mediaUrl = !empty($this->getMediaUrl($rsp->MediaUrl)) ? $this->getMediaUrl($rsp->MediaUrl) : "";
        //删除临时文件
        unlink($absoluteUrl);
        //保存数据库
        $videoId = $this->save($fileId,$mediaUrl);
        //执行转码任务
        $procedure = $this->processMedia($fileId);
        if($videoId && $procedure){
            return ['videoId'=>$videoId,'fileId'=>$fileId,'mediaUrl'=>$mediaUrl];
        }else{
            return false;
        }
    }

    private function getMediaUrl($mediaUrl)
    {
        $settings = app(SettingsRepository::class);
        $urlKey = $settings->get('qcloud_vod_url_key', 'qcloud');
        $urlExpire = (int)$settings->get('qcloud_vod_url_expire', 'qcloud');
        if ($urlKey  && !empty($mediaUrl)) {
            $currentTime = Carbon::now()->timestamp;
            $dir = Str::beforeLast(parse_url($mediaUrl)['path'], '/') . '/';
            $t = dechex($currentTime + $urlExpire);
            $us = Str::random(10);
            $sign = md5($urlKey . $dir . $t . $us);
            $mediaUrl = $mediaUrl . '?t=' . $t . '&us=' . $us . '&sign=' . $sign;
        }
        return $mediaUrl;
    }

    //保存到数据库
    private function save($fileId,$mediaUrl){
        if(empty($fileId)){
            return false;
        }
        try {
            $threadVideo = new ThreadVideo();
            $threadVideo->file_id = $fileId;
            $threadVideo->media_url = $mediaUrl;
            $threadVideo->thread_id = 0;
            $threadVideo->post_id = 0;
            $threadVideo->user_id = 0;
            $threadVideo->save();
            return $threadVideo->id;
        }catch (\Exception $e){
            DzqLog::error('数据库异常', $e->getMessage());
            return false;
        }
    }

    //转码
    private function processMedia($fileId){
        if(empty($fileId)){
            return false;
        }
        try {
            $settingRepo = app(SettingsRepository::class);
            $secretId = $settingRepo->get('qcloud_secret_id', 'qcloud');
            $secretKey = $settingRepo->get('qcloud_secret_key', 'qcloud');

            $cred = new Credential($secretId, $secretKey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint($this->url);

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new VodClient($cred, "", $clientProfile);
            $req = new ProcessMediaRequest();
            $params = array(
                'FileId'=>$fileId
            );
            $req->fromJsonString(json_encode($params));
            $resp = $client->ProcessMedia($req);
            $resp = json_decode($resp->toJsonString(),true);
            if(empty($resp['TaskId'])){
                DzqLog::error('转码任务未执行');
            }
            return true;
        }catch (\Exception $e){
            DzqLog::error('转码异常', $e->getMessage());
            return false;
        }
    }
}
