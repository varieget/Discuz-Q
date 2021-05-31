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

namespace App\Common;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Utils
{
    public static function diffTime($time)
    {
        $diff = time() - strtotime($time);
        if ($diff < 60) {
            return $diff . '秒前';
        }
        if ($diff >= 60 && $diff < 60 * 60) {
            return intval($diff / 60.0) . '分钟前';
        }
        if ($diff >= 60 * 60 && $diff < 24 * 60 * 60) {
            return intval($diff / (60 * 60.0)) . '小时前';
        }
        return date('Y-m-d H:i:s', strtotime($time));
    }

    public static function pluckArray(&$array, $field)
    {
        $result = [];
        foreach ($array as $item) {
            $result[$item[$field]] [] = $item;
        }
        $array = $result;
        return $result;
    }

    /**
     *字段名称转小驼峰格式
     * @param $array
     * @return array
     */
    public static function paginateBeauty(&$array)
    {
        $data = [];
        foreach ($array as $k => $v) {
            $data[Str::camel($k)] = $v;
        }
        $array = $data;
        return $data;
    }

    public static function getDzqStorage()
    {
        $server = Request::capture()->server();
        return dirname($server['DOCUMENT_ROOT']) . '/storage';
    }

    public static function getDzqDomain()
    {
        return Request::capture()->getSchemeAndHttpHost();
    }

    /**
     * 用指定的转换方法，转换数组的键格式
     *
     * @param array $arr
     * @param callable $transformer
     * @param array $exceptKeys
     *
     * @return array
     */
    public static function arrayKeysTransform(array $arr, callable $transformer, array $exceptKeys = []): array
    {
        foreach ($arr as $k => $v) {
            if (in_array($k, $exceptKeys, true)) {
                continue;
            }
            unset($arr[$k]);
            $k = $transformer($k);
            if (is_array($v)) {
                $v = static::arrayKeysTransform($v, $transformer, $exceptKeys);
            }
            $arr[$k] = $v;
        }
        return $arr;
    }

    /**
     * 把数组的键转换为小驼峰
     *
     * @param array $params
     * @param array $exceptKeys
     *
     * @return array
     */
    public static function arrayKeysToCamel(array $params, array $exceptKeys = []): array
    {
        return static::arrayKeysTransform($params, [\Illuminate\Support\Str::class, 'camel'], $exceptKeys);
    }

    /**
     * 把数组的键转换为下划线
     *
     * @param array $params
     * @param array $exceptKeys
     *
     * @return array
     */
    public static function arrayKeysToSnake(array $params, array $exceptKeys = []): array
    {
        return static::arrayKeysTransform($params, [\Illuminate\Support\Str::class, 'snake'], $exceptKeys);
    }

    public static function logOldPermissionPosition($method)
    {
        if (!app()->has('permLog')) {
            $logConfig = [
                'alias' => 'permLog',
                'path' => 'logs/permLog.log',
                'level' => Logger::INFO,
            ];

            $handler = new RotatingFileHandler(
                storage_path(Arr::get($logConfig, 'path')),
                200,
                Arr::get($logConfig, 'level')
            );
            $handler->setFormatter(new LineFormatter(null, null, true, true));
            app()->instance(Arr::get($logConfig, 'alias'), new Logger(Arr::get($logConfig, 'alias'), [$handler]));
            app()->alias(Arr::get($logConfig, 'alias'), LoggerInterface::class);
        }

        /** @var LoggerInterface $logger */
        $logger = app('permLog');
        $logger->info('in: '.$method.': '.json_encode(debug_backtrace()));
    }
}
