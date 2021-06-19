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

use App\Api\Serializer\AttachmentSerializer;
use App\Commands\Attachment\AttachmentUploader;
use App\Common\ResponseCode;
use App\Events\Attachment\Saving;
use App\Events\Attachment\Uploaded;
use App\Events\Attachment\Uploading;
use App\Models\Attachment;
use App\Repositories\UserRepository;
use App\Validators\AttachmentValidator;
use Discuz\Base\DzqController;
use Discuz\Foundation\EventsDispatchTrait;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;


class CreateAttachmentController extends DzqController
{
    use EventsDispatchTrait;

    protected $events;

    protected $validator;

    protected $uploader;

    public function __construct(Dispatcher $events, AttachmentValidator $validator, AttachmentUploader $uploader)
    {
        $this->events = $events;
        $this->validator = $validator;
        $this->uploader = $uploader;
    }

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
        $file = Arr::get($this->request->getUploadedFiles(), 'file');
        $name = Arr::get($this->request->getParsedBody(), 'name', '');
        $type = (int) Arr::get($this->request->getParsedBody(), 'type', 0);
        $order = (int) Arr::get($this->request->getParsedBody(), 'order', 0);
        $ipAddress = ip($this->request->getServerParams());
        ini_set('memory_limit',-1);

        $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
        $tmpFile = tempnam(storage_path('/tmp'), 'attachment');
        $tmpFileWithExt = $tmpFile . ($ext ? ".$ext" : '');
        // 上传临时目录之前验证

        $this->validator->valid([
            'type' => $type,
            'file' => $file,
            'size' => $file->getSize(),
            'ext' => strtolower($ext),
        ]);
        $file->moveTo($tmpFileWithExt);

        try {
            $file = new UploadedFile(
                $tmpFileWithExt,
                $file->getClientFilename(),
                $file->getClientMediaType(),
                $file->getError(),
                true
            );

            $this->events->dispatch(
                new Uploading($actor, $file)
            );

            // 上传
            $this->uploader->upload($file, $type);

            $this->events->dispatch(
                new Uploaded($actor, $this->uploader)
            );

            $width = 0;
            $height = 0;
            if(in_array($type,[1,4,5])){
                list($width, $height) = getimagesize($tmpFileWithExt);
            }

            $attachment = Attachment::build(
                $actor->id,
                $type,
                $this->uploader->fileName,
                $this->uploader->getPath(),
                $name ?: $file->getClientOriginalName(),
                $file->getSize(),
                $file->getClientMimeType(),
                $this->uploader->isRemote(),
                Attachment::APPROVED,
                $ipAddress,
                $order,
                $width,
                $height
            );

            $this->events->dispatch(
                new Saving($attachment, $this->uploader, $actor)
            );

            $attachment->save();

            $this->dispatchEventsFor($attachment);
        } finally {
            @unlink($tmpFile);
            @unlink($tmpFileWithExt);
        }
        $attachmentSerializer = $this->app->make(AttachmentSerializer::class);
        $attachment = $attachmentSerializer->getDefaultAttributes($attachment);
        $data = $this->camelData($attachment);
        return $this->outPut(ResponseCode::SUCCESS,'',$data);
    }
}
