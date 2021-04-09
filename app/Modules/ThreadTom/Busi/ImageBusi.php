<?php
/**
 * Copyright (C) 2021 Tencent Cloud.
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

namespace App\Modules\ThreadTom\Busi;

use App\Modules\ThreadTom\TomBaseBusi;

class ImageBusi extends TomBaseBusi
{
    public function create()
    {

        $imageIds = $this->getParams('imageIds');
        $desc = $this->getParams('desc');


        //todo logic


        return $this->jsonReturn(
            [
                [
                    'id' => 1,
                    'user_id' => 5,
                    'type_id' => 5,
                    'order' => 0,
                    'type' => 1,
                    'is_remote' => 0,
                    'is_approved' => 1,
                    'attachment' => 'NFRFVwledPOg7fsIUqB5gkSHZpwuuAOfrhO48q5O.jpeg',
                    'file_path' => 'public/attachments/2021/02/26/',
                    'file_name' => '0000.zip',
                    'file_size' => '2006583',
                    'file_type' => 'application/zip'
                ],
                [
                    'id' => 2,
                    'user_id' => 5,
                    'type_id' => 5,
                    'order' => 0,
                    'type' => 1,
                    'is_remote' => 0,
                    'is_approved' => 1,
                    'attachment' => 'NFRFVwledPOg7fsIUqB5gkSHZpwuuAOfrhO48q5O.jpeg',
                    'file_path' => 'public/attachments/2021/02/26/',
                    'file_name' => '0000.zip',
                    'file_size' => '2006583',
                    'file_type' => 'application/zip'
                ],
                [
                    'id' => 3,
                    'user_id' => 5,
                    'type_id' => 5,
                    'order' => 0,
                    'type' => 1,
                    'is_remote' => 0,
                    'is_approved' => 1,
                    'attachment' => 'NFRFVwledPOg7fsIUqB5gkSHZpwuuAOfrhO48q5O.jpeg',
                    'file_path' => 'public/attachments/2021/02/26/',
                    'file_name' => '0000.zip',
                    'file_size' => '2006583',
                    'file_type' => 'application/zip'
                ]
            ]
        );

    }


    public function update()
    {
        return $this->jsonReturn([
           'name'=>'hello'
        ]);
    }

    public function select()
    {
        return $this->jsonReturn( [
            [
                'attachment' => 'QlOUPSnF4ylp64UUicDXJhawB129OZS3n7i7zwa4.jpeg',
                'extension' => 'jpeg',
                'fileName' => "11.jpeg",
                'filePath' => "public/attachments/2021/03/30/",
                'fileSize' => 260739,
                'fileType' => "image/jpeg",
                'id' => 166,
                'isApproved' => 1,
                'isRemote' => false,
                'order' => 0,
                'thumbUrl' => "http://dev.discuz.com/storage/attachments/2021/03/30/QlOUPSnF4ylp64UUicDXJhawB129OZS3n7i7zwa4_thumb.jpeg",
                'type' => 1,
                'typeId' => 109,
                'url' => "http://dev.discuz.com/storage/attachments/2021/03/30/QlOUPSnF4ylp64UUicDXJhawB129OZS3n7i7zwa4.jpeg"
            ],
            [
                'attachment' => 'QlOUPSnF4ylp64UUicDXJhawB129OZS3n7i7zwa4.jpeg',
                'extension' => 'jpeg',
                'fileName' => "11.jpeg",
                'filePath' => "public/attachments/2021/03/30/",
                'fileSize' => 260739,
                'fileType' => "image/jpeg",
                'id' => 166,
                'isApproved' => 1,
                'isRemote' => false,
                'order' => 0,
                'thumbUrl' => "http://dev.discuz.com/storage/attachments/2021/03/30/QlOUPSnF4ylp64UUicDXJhawB129OZS3n7i7zwa4_thumb.jpeg",
                'type' => 1,
                'typeId' => 109,
                'url' => "http://dev.discuz.com/storage/attachments/2021/03/30/QlOUPSnF4ylp64UUicDXJhawB129OZS3n7i7zwa4.jpeg"
            ],
            [
                'attachment' => 'QlOUPSnF4ylp64UUicDXJhawB129OZS3n7i7zwa4.jpeg',
                'extension' => 'jpeg',
                'fileName' => "11.jpeg",
                'filePath' => "public/attachments/2021/03/30/",
                'fileSize' => 260739,
                'fileType' => "image/jpeg",
                'id' => 166,
                'isApproved' => 1,
                'isRemote' => false,
                'order' => 0,
                'thumbUrl' => "http://dev.discuz.com/storage/attachments/2021/03/30/QlOUPSnF4ylp64UUicDXJhawB129OZS3n7i7zwa4_thumb.jpeg",
                'type' => 1,
                'typeId' => 109,
                'url' => "http://dev.discuz.com/storage/attachments/2021/03/30/QlOUPSnF4ylp64UUicDXJhawB129OZS3n7i7zwa4.jpeg"
            ]
        ]);
    }
}
