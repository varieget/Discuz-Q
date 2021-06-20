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

use App\Models\Attachment;
use Discuz\Console\AbstractCommand;
use Discuz\Contracts\Setting\SettingsRepository;
use Intervention\Image\ImageManager;

class AttachmentAttributeUpdateCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'attachment:update';

    protected $settings;

    public $image;
    /**
     * {@inheritdoc}
     */
    protected $description = '更新附件历史图片宽高';

    public function __construct(string $name = null, SettingsRepository $settings,ImageManager $image) {
        parent::__construct($name);
        $this->settings = $settings;
        $this->image = $image;
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
                        $jsonData = file_get_contents($url."?imageInfo");
                        $ArrData = json_decode($jsonData,true);
                        $width = (int)$ArrData['width'];
                        $height = (int)$ArrData['height'];
                        $image->file_width = $width;
                        $image->file_height = $height;
                        if($image->save()){
                            $log->info("附件图片更新成功 attachmentId：{$image->id}，fileWidth：{$image->file_width}，fileHeight：{$image->file_height}");
                        }else{
                            $log->info("附件图片更新失败 attachmentId：{$image->id}，oldFileWidth：{$width}，oldFileHeight：{$height}");
                        }
                    } else {
                        $imageManage = $this->image->make(
                            storage_path('app/' . $image->full_path)
                        );
                        $width = (int) $imageManage->width();
                        $height = (int) $imageManage->height();
                        $image->file_width = $width;
                        $image->file_height = $height;
                        if($image->save()){
                            $log->info("附件图片更新成功 attachmentId：{$image->id}，fileWidth：{$image->file_width}，fileHeight：{$image->file_height}");
                        }else{
                            $log->info("附件图片更新失败 attachmentId：{$image->id}，oldFileWidth：{$width}，oldFileHeight：{$height}");
                        }
                    }
                });
            }
        }catch (\Exception $e){
            $log->error("附件图片更新失败 Exception:".$e->getMessage());
        }
        $this->info('更新历史图片宽高脚本执行 [结束]');
    }
}
