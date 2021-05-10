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


class DzqCache
{
    /**
     * @desc 从缓存中提取指定id集合的数据，没有则从数据库查询
     * @param string $cacheKey 缓存key
     * @param array $extractIds 需要提取的数据
     * @param callable|null $callback 提取的数据不全则需要自行查询，查询结果会重新放进缓存
     * @return array|bool 返回从缓存中查询出的数据
     */
    public static function extractCacheData($cacheKey, $extractIds, callable $callback = null)
    {
        $cache = app('cache');
        $cacheData = $cache->get($cacheKey);
        $ret = [];
//        $cacheData = false;
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
            $ret = $callback($extractIds);
            !$cacheData && $cacheData = [];
            foreach ($ret as $key => $value) {
                $cacheData[$key] = $value;
            }
            $cache->put($cacheKey, $cacheData);
        }
        return $ret;
    }
}
