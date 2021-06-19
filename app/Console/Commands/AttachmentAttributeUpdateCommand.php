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

namespace App\Console\Commands;

use App\Api\Serializer\AttachmentSerializer;
use App\Models\Attachment;
use Carbon\Carbon;
use Discuz\Console\AbstractCommand;
use Discuz\Contracts\Setting\SettingsRepository;
use Discuz\Foundation\Application;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;

class AttachmentAttributeUpdateCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'attachment:update';

    protected $filesystem;

    protected $settings;
    /**
     * {@inheritdoc}
     */
    protected $description = '更新附件历史图片宽高';

    protected $attachmentSerializer;

    protected $app;

    public function __construct(string $name = null,Application $app,AttachmentSerializer $attachmentSerializer,Filesystem $filesystem, SettingsRepository $settings) {
        parent::__construct($name);
        $this->attachmentSerializer = $attachmentSerializer;
        $this->filesystem = $filesystem;
        $this->settings = $settings;
        $this->app = $app;
    }

    /**
     * clear attachment
     */
    protected function handle()
    {
        $this->info('更新历史图片宽高脚本执行 [开始]');
        $this->info('');

        $type = [Attachment::TYPE_OF_IMAGE,Attachment::TYPE_OF_DIALOG_MESSAGE,Attachment::TYPE_OF_ANSWER];
        $attachments = Attachment::query()
            ->whereIn('type',$type)
            ->where('file_width',0)
            ->where('file_height',0)
            ->limit(100)
            ->orderByDesc('id')
            ->get();
        $log = app('log');
        try {
            if($attachments){
                $attachments->map(function (Attachment $image) use ($log) {
                    if ($image['is_remote']) {
                        $remoteServer = $this->settings->get('qcloud_cos_cdn_url', 'qcloud', true);
                        $right =  substr($remoteServer, -1);
                        if("/"==$right){
                            $remoteServer = substr($remoteServer,0,strlen($remoteServer)-1);
                        }
                        $url = $remoteServer."/".$image->full_path;
                    } else {
                        $url = $this->request->getUri();
                        $port = $url->getPort();
                        $port = $port == null ? '' : ':' . $port;
                        $path = $url->getScheme() . '://' . $url->getHost() . $port;
                        $url = $path."/".$image->full_path;
                    }
                    $pathInfo = pathinfo($url);
                    $allExt = ['jpeg','jpg','gif','png','swf','swc','psd','tiff','bmp','iff'];
                    if(in_array($pathInfo['extension'],$allExt)){
                        list($width, $height) = getimagesize($url);
                        $image->file_width = $width;
                        $image->file_height = $height;
                        $image->save();
                        $log->info("附件图片更新成功 attachmentId：{$image->id}，fileWidth：{$image->file_width}，fileHeight：{$image->file_height}");
                    }
                });
            }
        }catch (\Exception $e){
            $log->error("附件图片更新失败 Exception:".$e->getMessage());
        }
        $this->info('更新历史图片宽高脚本执行 [结束]');
    }
}
