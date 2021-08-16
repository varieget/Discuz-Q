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

use App\Censor\Censor;
use App\Commands\Attachment\AttachmentUploader;
use App\Common\ResponseCode;
use App\Models\Attachment;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Illuminate\Http\UploadedFile;

class RelationAttachmentController extends DzqController
{
    use AttachmentTrait;

    protected $censor;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $type = (int) $this->inPut('type'); //0 附件 1图片 2视频 3音频 4消息图片
        $this->checkUploadAttachmentPermissions($type, $this->user, $userRepo);
        return true;
    }

    public function __construct(Censor $censor, ImageManager $image, AttachmentUploader $uploader)
    {
        $this->censor   = $censor;
        $this->image    = $image;
        $this->uploader = $uploader;
    }

    public function main()
    {
        $data = [
            'cosUrl' => $this->inPut('cosUrl'),
            'type' => (int)$this->inPut('type'),
            'fileName' => $this->inPut('fileName')
        ];

        $this->dzqValidate($data, [
                'cosUrl' => 'required',
                'type' => 'required|integer|in:0,1,2,3,4',
                'fileName' => 'required|max:200'
            ]
        );

        $cosUrl = $data['cosUrl'];
        $cosSigns =  strstr($cosUrl,'?');
        if (in_array($data['type'], [Attachment::TYPE_OF_IMAGE, Attachment::TYPE_OF_DIALOG_MESSAGE])) {
            if ($cosSigns && strstr($cosSigns, 'q-sign-algorithm') && strstr($cosSigns, 'q-signature')) {
                $getCosInfoUrl = $cosUrl . '&imageInfo';
                $thumbUrl = $cosUrl . '&imageMogr2/thumbnail/' . Attachment::FIX_WIDTH . 'x' . Attachment::FIX_WIDTH;
            } else {
                $getCosInfoUrl = $cosUrl . '?imageInfo';
                $thumbUrl = $cosUrl . '?imageMogr2/thumbnail/' . Attachment::FIX_WIDTH . 'x' . Attachment::FIX_WIDTH;
            }
            $cosAttachmentData = @file_get_contents($getCosInfoUrl, false, stream_context_set_default(['ssl'=>['verify_peer'=>false, 'verify_peer_name'=>false]]));
            $this->censor->checkImage($cosUrl, true);
        } else {
            $cosAttachmentData = @file_get_contents($cosUrl, false, stream_context_set_default(['ssl'=>['verify_peer'=>false, 'verify_peer_name'=>false]]));
            $fileSize = strlen($cosAttachmentData);
        }

        if ($cosAttachmentData) {
            $cosAttachmentData = json_decode($cosAttachmentData, true);
            $fileData = parse_url($cosUrl);
            $fileData = pathinfo($fileData['path']);
            $width = $cosAttachmentData['width'] ?? 0;
            $height = $cosAttachmentData['height'] ?? 0;
            $fileSize = $cosAttachmentData['size'] ?? $fileSize;
            $ext = $cosAttachmentData['format'] ?? $fileData['extension'];
        } else {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '未获取到文件信息！');
        }

        $this->checkAttachmentExt($data['type'], $ext);
        $this->checkAttachmentSize($fileSize);
        $mimeType = $this->getAttachmentMimeType($cosUrl);
        $filePath = substr_replace($fileData['dirname'], '', strpos($fileData['dirname'], '/'), strlen('/')) . '/';
        $attachmentName = urldecode($fileData['basename']);

        // 模糊图处理
        if ($data['type'] == Attachment::TYPE_OF_IMAGE) {
            $tmpFile = tempnam(storage_path('/tmp'), 'attachment');
            $tmpFileWithExt = $tmpFile . $ext;
            @file_put_contents($tmpFileWithExt, @file_get_contents($cosUrl, false, stream_context_create(['ssl'=>['verify_peer'=>false, 'verify_peer_name'=>false]])));
            $blurImageFile = new UploadedFile(
                $tmpFileWithExt,
                $attachmentName,
                $mimeType,
                0,
                true
            );
            // 帖子图片自适应旋转
            if(strtolower($ext) != 'gif' && extension_loaded('exif')) {
                $this->image->make($tmpFileWithExt)->orientate()->save();
            }

            $this->uploader->put($data['type'], $blurImageFile, $attachmentName, $filePath);
            @unlink($tmpFile);
            @unlink($tmpFileWithExt);
        }

        $attachment = new Attachment();
        $attachment->uuid = Str::uuid();
        $attachment->user_id = $this->user->id;
        $attachment->type = $data['type'];
        $attachment->is_approved = Attachment::APPROVED;
        $attachment->attachment = $attachmentName;
        $attachment->file_path = $filePath;
        $attachment->file_name = $data['fileName'];
        $attachment->file_size = $fileSize;
        $attachment->file_width = $width;
        $attachment->file_height = $height;
        $attachment->file_type = $mimeType;
        $attachment->is_remote = Attachment::YES_REMOTE;
        $attachment->ip = ip($this->request->getServerParams());
        $attachment->save();
        $attachment->url = $cosUrl;
        $attachment->thumbUrl = $thumbUrl ?? '';

        $this->outPut(ResponseCode::SUCCESS, '', $this->camelData($attachment));
    }
}
