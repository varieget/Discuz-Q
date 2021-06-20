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
use Discuz\Foundation\Application;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class AttachmentAttributeUpdateCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'attachment:update';

    protected $settings;

    public $image;

    protected $filesystem;

    protected $app;
    /**
     * {@inheritdoc}
     */
    protected $description = '更新附件历史图片宽高';

    public function __construct(string $name = null,Application $app, SettingsRepository $settings,ImageManager $image) {
        parent::__construct($name);
        $this->settings = $settings;
        $this->image = $image;
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
        $log = app('log');

        $dateTime = date('Y-m-d H:i:s',strtotime("2021-06-20 17:00:00"));
        $attachments = Attachment::query()
            ->whereIn('type',$type)
            ->where('file_width',0)
            ->where('file_height',0)
            ->where('updated_at','<',$dateTime)
            ->limit(100)
            ->orderByDesc('id')
            ->get();
        try {
            if($attachments){
                $attachments->map(function (Attachment $image) use ($log) {
                    if ($image['is_remote']) {
                        $remoteServer = $this->settings->get('qcloud_cos_cdn_url', 'qcloud', true);
                        $right =  substr($remoteServer, -1);
                        if("/"==$right){
                            $remoteServer = substr($remoteServer,0,strlen($remoteServer)-1);
                        }
                        $remoteUrl = $remoteServer."/".$image->full_path;
                        $fileData = file_get_contents($remoteUrl,false, stream_context_create(['ssl'=>['verify_peer'=>false, 'verify_peer_name'=>false]]));
                        if($fileData){
                            $extension =Str::afterLast($image['attachment'], '.');
                            $fileName = $image['file_name'];
                            $temFileName = md5($fileName);
                            file_put_contents(storage_path('tmp/').$temFileName.".".$extension,$fileData);
                            $imageManage = $this->image->make(
                                storage_path('tmp/').$temFileName.".".$extension
                            );
                            $width = (int) $imageManage->width();
                            $height = (int) $imageManage->height();
                            $image->file_width = $width;
                            $image->file_height = $height;
                            $image->updated_at = date('Y-m-d H:i:s',time());
                            if($image->save()){
                                $log->info("附件图片更新成功 attachmentId：{$image->id}，fileWidth：{$image->file_width}，fileHeight：{$image->file_height}");
                            }else{
                                $log->info("附件图片更新失败 attachmentId：{$image->id}，oldFileWidth：{$width}，oldFileHeight：{$height}");
                            }
                            unlink(storage_path('tmp/').$temFileName.".".$extension);
                        }else{
                            $image->file_width = 0;
                            $image->file_height = 0;
                            $image->updated_at = date('Y-m-d H:i:s',time());
                            $image->save();
                            $log->info("附件图片不存在 attachmentId：{$image->id}");
                        }
                    } else {
                        if(file_exists(storage_path('app/' . $image->full_path))){
                            $imageManage = $this->image->make(
                                storage_path('app/' . $image->full_path)
                            );
                            $width = (int) $imageManage->width();
                            $height = (int) $imageManage->height();
                            $image->file_width = $width;
                            $image->file_height = $height;
                            $image->updated_at = date('Y-m-d H:i:s',time());
                            if($image->save()){
                                $log->info("附件图片更新成功 attachmentId：{$image->id}，fileWidth：{$image->file_width}，fileHeight：{$image->file_height}");
                            }else{
                                $log->info("附件图片更新失败 attachmentId：{$image->id}，oldFileWidth：{$width}，oldFileHeight：{$height}");
                            }
                        }else{
                            $image->file_width = 0;
                            $image->file_height = 0;
                            $image->updated_at = date('Y-m-d H:i:s',time());
                            $image->save();
                            $log->info("附件图片不存在 attachmentId：{$image->id}");
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
