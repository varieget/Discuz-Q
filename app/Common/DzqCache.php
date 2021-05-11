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

use Illuminate\Database\Eloquent\Collection;

class DzqCache
{
    /**
     * @desc 从缓存中提取指定id集合的数据，没有则从数据库查询
     * @param string $cacheKey 缓存key
     * @param array|string|integer $extractIds 需要提取的数据
     * @param callable|null $callback 提取的数据不全则需要自行查询，查询结果会重新放进缓存
     * @param bool $autoCache
     * @return array|bool 返回从缓存中查询出的数据
     */
    public static function extractCacheArrayData($cacheKey, $extractIds, callable $callback = null, $autoCache = true)
    {
        $cache = app('cache');
        $cacheData = $cache->get($cacheKey);
        $ret = [];
        $ids = $extractIds;
        !is_array($extractIds) && $extractIds = [$extractIds];
        if (!empty($extractIds)) {
            if ($cacheData) {
                foreach ($extractIds as $extractId) {
                    if (array_key_exists($extractId, $cacheData)) {
                        !empty($cacheData[$extractId]) && $ret[$extractId] = $cacheData[$extractId];
                    } else {
                        $ret = false;
                    }
                }
            }
        }
        if (($ret === false || !$cacheData) && !empty($callback)) {
            $ret = $callback($ids);
            if ($autoCache) {
                !$cacheData && $cacheData = [];
                foreach ($ret as $key => $value) {
                    $cacheData[$key] = $value;
                }
                $cache->put($cacheKey, $cacheData);
            }
        }
        return $ret;
    }

    /**
     * @desc 从缓存中提取指定id集合的数据，没有则从数据库查询
     * @param string $cacheKey 缓存key
     * @param array|string|integer $extractIds 需要提取的数据
     * @param callable|null $callback 提取的数据不全则需要自行查询，查询结果会重新放进缓存
     * @return array|bool 返回从缓存中查询出的数据
     */
    public static function extractCacheCollectionData($cacheKey, $extractIds, callable $callback = null)
    {
        $cache = app('cache');
        $cacheData = $cache->get($cacheKey);
        $ret = new  Collection();
        !is_array($extractIds) && $extractIds = [$extractIds];
        if (!empty($extractIds)) {
            if ($cacheData) {
                foreach ($extractIds as $extractId) {
                    if ($cacheData->has($extractId)) {
                        !empty($cacheData[$extractId]) && $ret->put($extractId, $cacheData[$extractId]);
                    } else {
                        $ret = false;
                    }
                }
            }
        }
        if (($ret === false || !$cacheData) && !empty($callback)) {
            $ret = $callback($extractIds);
            !$cacheData && $cacheData = new  Collection();
            foreach ($ret as $key => $value) {
                $cacheData->put($key, $value);
            }
            $cache->put($cacheKey, $cacheData);
        }
        return $ret;
    }

    public static function extractThreadListData($cacheKey, $filterId, $page, callable $callback = null, $preload = false)
    {
        $cache = app('cache');
        $cacheData = $cache->get($cacheKey);
        $ret = false;
        if ($cacheData) {
            if (array_key_exists($filterId, $cacheData) && array_key_exists($page, $cacheData[$filterId])) {
                $ret = $cacheData[$filterId][$page];
            }
        }
        if (($ret === false || !$cacheData) && !empty($callback)) {
            $ret = $callback($filterId, $page);
            !$cacheData && $cacheData = [];
            if ($preload) {
                $cacheData[$filterId] = $ret;
                $ret = $cacheData[$filterId][$page];
            } else {
                $cacheData[$filterId][$page] = $ret;
            }
            $cache->put($cacheKey, $cacheData);
        }
        return $ret;
    }


    public static function removeCacheByIds()
    {

    }
}
