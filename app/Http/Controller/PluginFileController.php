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
namespace App\Http\Controller;

use App\Common\ResponseCode;
use Discuz\Common\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PluginFileController implements RequestHandlerInterface
{

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $pluginList = Utils::getPluginList();
        $pluginList = array_values($pluginList);
        $pluginList = array_column($pluginList, null, 'name_en');
        $pluginName = $query['plugin_name'];
        $config = $pluginList[$pluginName];
        $plugin = $config['plugin_' . $config['app_id']];
        $filePath = $plugin['view'] . '/dist/' . $query['file_path'];
        if (file_exists($filePath)) {
            exit(file_get_contents($filePath));
        } else {
            Utils::outPut(ResponseCode::RESOURCE_NOT_FOUND);
        }
    }
}
