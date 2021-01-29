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

namespace App\Api\Controller\SwitchSkin;

use App\Events\Setting\Saved;
use App\Events\Setting\Saving;
use App\Models\User;
use App\Models\setting;
use App\Models\Permission;
use App\Validators\SetSettingValidator;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Contracts\Setting\SettingsRepository;
use Discuz\Foundation\Application;
use Discuz\Http\DiscuzResponseFactory;
use Exception;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SwitchSkinController implements RequestHandlerInterface
{
    use AssertPermissionTrait;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * @param Events $events
     * @param SettingsRepository $settings
     * @param SetSettingValidator $validator
     */
    public function __construct(Events $events, SettingsRepository $settings, User $actor, Application $app)
    {
        $this->events = $events;
        $this->settings = $settings;
        $this->actor = $actor;
        $this->app = $app;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws PermissionDeniedException
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->assertAdmin($request->getAttribute('actor'));
        $actor = $request->getAttribute('actor');
        $body = $request->getParsedBody();
        $data = Arr::get($body, 'data', []);
        $attributes = Arr::get($data, 'attributes', []);

        $public_path = public_path();
        $last_path = dirname($public_path);
        $link_skin_public = $last_path . DIRECTORY_SEPARATOR .'public_'. $attributes['skin'];

        $status = false;
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
            $this->copy_dir($link_skin_public, $public_path);
            if(is_dir($public_path)){
                if($dh = opendir($public_path)){
                    $skin_file = 'skin.conf';
                    if(file_exists($skin_file)){
                        $site_skin = file_get_contents($skin_file);
                        if($site_skin == $attributes['skin']){
                            $status = true;
                        }
                    }else{
                        throw new Exception("<p>您已丢失主题标识文件，无法判断您是否切换成功！</p>");
                    }
                }
            }
        }else{
            $cmd = '\\cp -r '. $link_skin_public . DIRECTORY_SEPARATOR . '* ' . $public_path. DIRECTORY_SEPARATOR;
            shell_exec($cmd);
            $skin_file = $public_path . DIRECTORY_SEPARATOR .'skin.conf';
            if(file_exists($skin_file)){
                $site_skin = file_get_contents($skin_file);
                if($site_skin == $attributes['skin']){
                    $status = true;
                }else{
                    throw new Exception("切换失败，请在站点目录下运行:$cmd");
                }
            }else{
                throw new Exception("<p>您已丢失主题标识文件，无法判断您是否切换成功！</p>");
            }
        }

        if($status){
            $result = [
                'data' => [
                    'attributes' => [
                        'site_skin' => (int)$attributes['skin'],
                        'code' => 200,
                        'message' => '主题切换成功！',
                    ],
                ]
            ];
        }else{
            $result = [
                'data' => [
                    'attributes' => [
                        'site_skin' => 1,
                        'code' => 500,
                        'message' => '主题切换失败！',
                    ],
                ]
            ];
        }

        return DiscuzResponseFactory::JsonResponse($result);
    }

    public function copy_dir($from_dir, $to_dir)
    {
        if(!is_dir($from_dir)){
            return false;
        }

        $from_files = scandir($from_dir);
         //如果不存在目标目录，则尝试创建
        if(!file_exists($to_dir)){
            @mkdir($to_dir);
        }
        if(!empty($from_files)){
            foreach ($from_files as $file){
                if($file == '.' || $file == '..' ){
                    continue;
                }

                if(is_dir($from_dir .'/'. $file)){
                    //如果是目录，则调用自身
                    $this->copy_dir($from_dir .'/'. $file, $to_dir .'/'. $file);
                }else{
                    //直接copy到目标文件夹
                    copy($from_dir .'/'. $file, $to_dir .'/'. $file);
                }
            }
        }
    }
}
