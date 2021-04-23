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

namespace App\Api\Controller\SettingsV3;

use App\Common\ResponseCode;
use App\Models\Setting;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Base\DzqController;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListSettingsController extends DzqController
{
    use AssertPermissionTrait;

    /**
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return array|mixed
     * @throws \Discuz\Auth\Exception\PermissionDeniedException
     */
    public function main()
    {
        $this->assertAdmin($this->user);
        $key = $this->inPut('key');
        $tag = $this->inPut('tag');
        $this->outPut(ResponseCode::SUCCESS, '', Setting::where([['key', $key], ['tag', $tag]])->get());
    }
}
