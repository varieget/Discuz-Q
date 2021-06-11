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
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
        }

        $attachment = Attachment::query()->where('id', $data['attachmentsId'])->first();
        if (empty($attachment)){
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

        //文件完整路径（这里将真实的文件存放在temp目录下）
        $filePath = $url;
        //以只读方式打开文件，并强制使用二进制模式
        $fileHandle = fopen($filePath,"rb");
        if ( !empty($fileHandle) ) {
            ob_clean();//清除一下缓冲区
            //获得文件名称
            $filename = basename(urldecode($attachment->file_name));
            //将utf8编码转换成gbk编码，否则，文件中文名称的文件无法打开
            $filePath = iconv('UTF-8','gbk',$filePath);
            /**
             * 这里应该加上安全验证之类的代码，例如：检测请求来源、验证UA标识等等
             */
            //文件类型是二进制流。设置为utf8编码（支持中文文件名称）
            header('Content-type:application/octet-stream; charset=utf-8');
            header("Content-Transfer-Encoding: binary");
            header("Accept-Ranges: bytes");
            //文件大小
            header("Content-Length: ".filesize($filePath));
            //触发浏览器文件下载功能
            header('Content-Disposition:attachment;filename="'.urlencode($filename).'"');
            //循环读取文件内容，并输出
            while(!feof($fileHandle)) {
                //从文件指针 handle 读取最多 length 个字节（每次输出10k）
                echo fread($fileHandle, 10240);
            }
            //关闭文件流
            fclose($fileHandle);
        }

        $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
    }
}
