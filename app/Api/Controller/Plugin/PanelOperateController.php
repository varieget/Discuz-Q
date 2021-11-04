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

namespace App\Api\Controller\Plugin;

use App\Common\CacheKey;
use App\Common\ResponseCode;
use Discuz\Base\DzqAdminController;
use Discuz\Base\DzqCache;
use Discuz\Common\Utils;
use Discuz\Console\Kernel;
use Discuz\Foundation\Application;

class PanelOperateController extends DzqAdminController
{

    public function main()
    {
        $appId = $this->inPut("appId");
        $operate = $this->inPut("operate");
        $pluginMap = Utils::getPluginList();
        if(!isset($pluginMap[$appId])){
            $this->outPut(ResponseCode::INVALID_PARAMETER,"没该插件");
        }
        $item = $pluginMap[$appId];

        switch ($operate){
            case 1:
                $this->release($item);
                break;
            case 2:
                $this->offline($item);
                break;
            case 3:
                $this->delete($item);
                break;
        }


        $this->outPut(ResponseCode::INVALID_PARAMETER);

    }

    public function suffixClearCache(){
        DzqCache::delKey(CacheKey::PLUGIN_LOCAL_CONFIG);
    }

    private function release($item){
        $pluginDir = base_path('plugin');
        $nameEn = $item["name_en"];
        $pathDir = $pluginDir.DIRECTORY_SEPARATOR.ucfirst($nameEn).DIRECTORY_SEPARATOR."config.json";
        $config = json_decode(file_get_contents($pathDir), 256);
        $config["status"] = 1;
        $strConfig = json_encode($config, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        file_put_contents($pathDir,$strConfig);

        //执行命令
        $this->runDBMigration($nameEn);

        $this->outPut(0,'', "发布成功");
    }


    private function runDBMigration($name)
    {
        try {
            return $this->runConsoleCommand('migrate:plugin', ['--force' => true, '--name='.$name]);
        } catch (Exception $e) {
            throw new Exception("发布失败，数据库执行失败：" . $e->getMessage());
        }
    }

    /**
     * 运行 php disco 命令
     */
    function runConsoleCommand($cmd, $params)
    {
//        // 访问私有属性
//        $reader = function & ($object, $property) {
//            $value = &Closure::bind(function & () use ($property) {
//                return $this->$property;
//            }, $object, $object)->__invoke();
//
//            return $value;
//        };
//        require __DIR__ . '/../vendor/autoload.php';
//        $app = new Application(realpath(__DIR__ . '/..'));
//        $console = $app->make(Kernel::class);
//        $console->call($cmd, $params);
//        $lastOutput = &$reader($console, 'lastOutput');
//        return $lastOutput->fetch();
    }

    private function offline($item){
        $pluginDir = base_path('plugin');
        $nameEn = $item["name_en"];
        $pathDir = $pluginDir.DIRECTORY_SEPARATOR.ucfirst($nameEn).DIRECTORY_SEPARATOR."config.json";
        $config = json_decode(file_get_contents($pathDir), 256);
        $config["status"] = 0;
        $strConfig = json_encode($config, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        file_put_contents($pathDir,$strConfig);

        $this->outPut(0,'', "下线成功");
    }

    private function delete($item){

        $pluginDir = base_path('plugin');
        $nameEn = $item["name_en"];
        $pathDir = $pluginDir.DIRECTORY_SEPARATOR.ucfirst($nameEn);
        $this->remove_dir($pathDir);

        $this->outPut(0,'', "删除成功");
    }

    function remove_dir($path)
    {
        if (empty($path) || !$path) {
            return false;
        }
        if(is_file($path)){
            @unlink($path);
        }else{
            $fileList = glob($path . DIRECTORY_SEPARATOR.'*');
            foreach ($fileList as $pathTemp){
                $this->remove_dir($pathTemp);
            }
            @rmdir($path);
        }
    }
}
