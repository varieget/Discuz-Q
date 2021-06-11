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

namespace App\Api\Controller\AttachmentV3;

use App\Common\ResponseCode;
use App\Models\Attachment;
use App\Models\AttachmentShare;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Discuz\Base\DzqController;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;

class DownloadAttachmentController extends DzqController
{

    protected $filesystem;

    protected $settings;

    public function __construct(Filesystem $filesystem, SettingsRepository $settings)
    {
        $this->filesystem = $filesystem;
        $this->settings = $settings;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return true;
    }

    public function main()
    {
        $data = [
            'sign' => $this->inPut('sign'),
            'attachmentsId' => $this->inPut('attachmentsId')
        ];

        $this->dzqValidate($data,[
            'sign' => 'required',
            'attachmentsId' => 'required|int',
        ]);

        $share = AttachmentShare::query()
            ->where(['sign' => $data['sign'], 'attachments_id' => $data['attachmentsId']])
            ->first();

        if (empty($share) || strtotime($share->expired_at) < time()) {
            app('log')->info("requestId：{$this->requestId},分享记录不存在，时间已过期");
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
        }

        $attachment = Attachment::query()->where('id', $data['attachmentsId'])->first();
        if (empty($attachment)){
            app('log')->info("requestId：{$this->requestId},附件不存在");
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
        }

        if ($attachment->is_remote) {
            $url = $this->settings->get('qcloud_cos_sign_url', 'qcloud', true)
                ? $this->filesystem->disk('attachment_cos')->temporaryUrl($attachment->full_path, Carbon::now()->addDay())
                : $this->filesystem->disk('attachment_cos')->url($attachment->full_path);
        } else {
            $url = $this->filesystem->disk('attachment')->url($attachment->full_path);
        }

        AttachmentShare::query()->where('sign', $data['sign'])->update([
            'download_count' => intval($share->download_count + 1),
            'updated_at' => Carbon::now()
        ]);

        //声明浏览器输出的是字节流
        header('Content-Type: application/octet-stream');
        //声明浏览器返回大小是按字节进行计算
        header('Accept-Ranges:bytes');
        //告诉浏览器文件的总大小
        $fileSize = filesize($url);//坑 filesize 如果超过2G 低版本php会返回负数
        header('Content-Length:' . $fileSize); //注意是'Content-Length:' 非Accept-Length
        //声明下载文件的名称
        header('Content-Disposition:attachment;filename=' . basename($attachment->file_name));//声明作为附件处理和下载后文件的名称
        //获取文件内容
        $handle = fopen($url, 'rb');//二进制文件用‘rb’模式读取
        while (!feof($handle) ) { //循环到文件末尾 规定每次读取（向浏览器输出为$readBuffer设置的字节数）
            echo fread($handle, 10240);
        }
        fclose($handle);//关闭文件句柄
        exit;
    }
}
