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
use App\Commands\Attachment\AttachmentUploader;
use App\Repositories\UserRepository;
use App\Validators\AttachmentValidator;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Illuminate\Http\UploadedFile;


class CreateAttachmentController extends DzqController
{
    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $type = $this->inPut('type') ?: 0;

        $typeMethodMap = [
            Attachment::TYPE_OF_FILE => [$userRepo, 'canInsertAttachmentToThread'],
            Attachment::TYPE_OF_IMAGE => [$userRepo, 'canInsertImageToThread'],
            Attachment::TYPE_OF_AUDIO => [$userRepo, 'canInsertAudioToThread'],
            Attachment::TYPE_OF_VIDEO => [$userRepo, 'canInsertVideoToThread'],
            Attachment::TYPE_OF_ANSWER => [$userRepo, 'canInsertRewardToThread'],
            Attachment::TYPE_OF_DIALOG_MESSAGE => [$userRepo, 'canCreateDialog'],
        ];
        // 不在这里面，则通过，后续会有 type 表单验证
        if (!isset($typeMethodMap[$type])) {
            return true;
        }

        return call_user_func_array($typeMethodMap[$type], [$this->user]);
    }

    public function main()
    {
        $actor = $this->user;
        $file = $this->request->getUploadedFiles()['file'];
        $name = $this->request->getParsedBody()['name'];
        $type = $this->inPut('type') ? $this->inPut('type') : 0;
        $order = $this->inPut('order') ? $this->inPut('order') : 0;
        $ipAddress = ip($this->request->getServerParams());
        ini_set('memory_limit',-1);

        $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        $tmpFile = tempnam(storage_path('/tmp'), 'attachment');
        $tmpFileWithExt = $tmpFile . ($ext ? ".$ext" : '');

        $file->moveTo($tmpFileWithExt);

        try {
            $file = new UploadedFile(
                $tmpFileWithExt,
                $file->getClientFilename(),
                $file->getClientMediaType(),
                $file->getError(),
                true
            );
            $validator = app()->make(AttachmentValidator::class);

            // 验证
            $validator->valid([
                'type' => $type,
                'file' => $file,
                'size' => $file->getSize(),
                'ext' => strtolower($ext),
            ]);

            $uploader = app()->make(AttachmentUploader::class);
            // 上传
            $uploader->upload($file, $file);

            $attachment = Attachment::build(
                $actor->id,
                $type,
                $uploader->fileName,
                $uploader->getPath(),
                $name ?: $file->getClientOriginalName(),
                $file->getSize(),
                $file->getClientMimeType(),
                $uploader->isRemote(),
                Attachment::APPROVED,
                $ipAddress,
                $order
            );

            $attachment->save();

        } finally {
            @unlink($tmpFile);
            @unlink($tmpFileWithExt);
        }

        return $this->outPut(ResponseCode::SUCCESS,'',$attachment);
    }
}
