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
use Carbon\Carbon;
use Discuz\Base\DzqController;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Http\UrlGenerator;
use Exception;
use Illuminate\Contracts\Filesystem\Factory as FileFactory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tobscure\JsonApi\Document;

class UploadLogoController extends DzqController
{
    use AssertPermissionTrait;

    /**
     * @var Factory
     */
    protected $validator;

    /**
     * @var FileFactory
     */
    protected $filesystem;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * 允许上传的类型
     *
     * @var array
     */
    protected $allowTypes = [
        'background_image',
        'watermark_image',
        'header_logo',
        'logo',
        'favicon',
    ];

    /**
     * @param Factory $validator
     * @param FileFactory $filesystem
     * @param UrlGenerator $url
     */
    public function __construct(Factory $validator, FileFactory $filesystem, UrlGenerator $url)
    {
        $this->validator = $validator;
        $this->filesystem = $filesystem;
        $this->url = $url;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return string[]
     * @throws FileNotFoundException
     * @throws PermissionDeniedException
     * @throws ValidationException
     * @throws Exception
     */
    public function main()
    {
        $actor = $this->user;

        $this->assertCan($actor, 'setting.site');

        UrlGenerator::setRequest($this->request);

        $type = $this->inPut('type') ? $this->inPut('type') : 'logo';
        $file = $this->request->getUploadedFiles()['logo'];
        if (! $file) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, 'file_not_found');
        }

        $verifyFile = new UploadedFile(
            $file->getStream()->getMetadata('uri'),
            $file->getClientFilename(),
            $file->getClientMediaType(),
            $file->getError(),
            true
        );

        $mimes = [
            'watermark_image' => 'mimes:png',
            'favicon' => 'mimes:jpeg,jpg,png,gif,ico,svg',
        ];

        $this->validator->make(
            ['type' => $type, 'logo' => $verifyFile],
            [
                'type' => [Rule::in($this->allowTypes)],
                'logo' => [
                    'required',
                    $mimes[$type] ?? 'mimes:jpeg,jpg,png,gif',
                    'max:5120'
                ]
            ]
        )->validate();

        $fileName = $type . '.' . $verifyFile->getClientOriginalExtension();

        try {
            // 开启 cos 时，再存一份，优先使用
            if (Setting::getValue('qcloud_cos', 'qcloud')) {
                $cosStream = clone $file->getStream();

                $this->filesystem->disk('cos')->put($fileName, $cosStream);
            }

            $this->filesystem->disk('public')->put($fileName, $file->getStream());
        } catch (Exception $e) {
            return $this->outPut(ResponseCode::INVALID_PARAMETER,'',$e);
        }

        Setting::modifyValue($type, $fileName, $type === 'watermark_image' ? 'watermark' : 'default');

        return $this->outPut(ResponseCode::SUCCESS,'', [
            'key' => 'logo',
            'value' => $this->url->to('/storage/'.$fileName) . '?' . Carbon::now()->timestamp,
            'tag' => 'default'
        ]);
    }
}