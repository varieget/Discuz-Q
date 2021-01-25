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

        $skin = Setting::query()->where('key', 'site_skin')->first();
        $operationSystem = strtoupper(substr(PHP_OS, 0, 3));
        $public_path = public_path();
        $last_path = dirname($public_path);
        $link_skin_public = $last_path . DIRECTORY_SEPARATOR .'public_'. $attributes['skin'];

        if($skin->value == $attributes['skin'] && is_link($public_path) && readlink($public_path) == $link_skin_public){
            throw new Exception("已是当前栏目，切换无效！");
        }else{
            if($operationSystem === 'WIN'){
                // windows
                if (!is_link($public_path) || readlink($public_path) !== $link_skin_public) {
                    throw new Exception("<p>建立软连接失败，请在服务器上以管理员身份打开命令提示符，运行</p><p>rmdir $public_path && mklink /d $public_path $link_skin_public</p><p>然后重试本步骤</p>");
                }else{
                    if(is_link($public_path) && readlink($public_path) == $link_skin_public){
                        Setting::query()->where('key', 'site_skin')->update(['value' => $attributes['skin']]);
                        $result = [
                            'data' => [
                                'attributes' => [
                                    'site_skin' => (int)$attributes['skin'],
                                    'code' => 200,
                                    'message' => '栏目切换成功！',
                                ],
                            ]
                        ];
                    }else{
                        throw new Exception("$public_path 已存在，并且指向不正确，请删除后，再重试本步骤");
                    }
                }
            }else{
                try{
                    $cmd = 'rm '. $public_path .' && ln -s '. $last_path . DIRECTORY_SEPARATOR .'public_'. $attributes['skin'] .' '. $public_path;
                    $result = false;
                    for($i = 0; $i < 10; $i++){
                        if(!is_link($public_path) || readlink($public_path) !== $link_skin_public){
                            shell_exec($cmd);
                        }else{
                            $result = true;
                            break;
                        }
                        sleep(0.1);
                    }

                    if($result){
                        Setting::query()->where('key', 'site_skin')->update(['value' => $attributes['skin']]);
                        $result = [
                            'data' => [
                                'attributes' => [
                                    'site_skin' => (int)$attributes['skin'],
                                    'code' => 200,
                                    'message' => '栏目切换成功！',
                                ],
                            ]
                        ];
                    }else{
                        throw new Exception("<p>切换栏目失败！</p>");
                    }
                }catch (Exception $e) {
                    throw $e;
                }
            }

            if($skin->value !== $attributes['skin'] && $attributes['skin'] == 1){
                $settings = Setting::query()->where('key', 'like', "%site_create_thread%")->get();
                $settings->each(function ($setting) {
                    $key = Arr::get($setting, 'key');
                    $value = 1;
                    $tag = Arr::get($setting, 'tag', 'default');
                    $this->settings->set($key, $value, $tag);
                });
                $this->events->dispatch(new Saved($settings));
            }
        }
        return DiscuzResponseFactory::JsonResponse($result);
    }
}
