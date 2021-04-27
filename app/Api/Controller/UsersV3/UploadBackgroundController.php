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

namespace App\Api\Controller\UsersV3;

use App\Commands\Users\UploadBackground;
use App\Common\ResponseCode;
use Discuz\Base\DzqController;
use Illuminate\Contracts\Bus\Dispatcher;

class UploadBackgroundController extends DzqController
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    public function main()
    {
        $uploadFile = $this->request->getUploadedFiles();
        if(empty($uploadFile)){
            $this->outPut(ResponseCode::INVALID_PARAMETER,'');
        }
        $file = $uploadFile['background'];
        $actor = $this->user;
        $id = $actor->id;
        $result = $this->bus->dispatch(
            new UploadBackground($id, $file, $actor)
        );
        $url = $this->request->getUri();
        $port = $url->getPort();
        $port = $port == null ? '' : ':' . $port;
        $path = $url->getScheme() . '://' . $url->getHost() . $port . '/';
        $result = [
            'id' => $result->id,
            'username' => $result->username,
            'backgroundUrl' => $path."storage/app/".$result->background,
            'updatedAt' => optional($result->updated_at)->format('Y-m-d H:i:s'),
            'createdAt' => optional($result->created_at)->format('Y-m-d H:i:s'),
        ];

        return $this->outPut(ResponseCode::SUCCESS,'', $result);
    }

}
