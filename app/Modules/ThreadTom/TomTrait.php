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

namespace App\Modules\ThreadTom;


trait TomTrait
{

    private $operations = ['create', 'delete', 'update', 'select'];

    /**
     * @desc 支持一次提交包含新建或者更新或者删除等各种类型混合
     * @param $tosArray
     * @return array
     */
    private function tosDispatcher($tosArray)
    {
        $config = TomConfig::$map;
        $text = '';
        $json = [];
        foreach ($tosArray as $k => $v) {
            if ($k == 'text') {
                $text = $v;
            } else {
                if (isset($v['tosId']) && isset($v['operation']) && isset($v['body'])) {
                    if (in_array($v['operation'], $this->operations)) {
                        $tosId = $v['tosId'];
                        $operation = $v['operation'];
                        $body = $v['body'];
                        if (isset($config[$tosId])) {
                            try {
                                $service = new \ReflectionClass($config[$tosId]['service']);
                                $service = $service->newInstanceArgs([$operation, $body]);
                                method_exists($service, $operation) && $json[$k] = $service->$operation();
                            } catch (\ReflectionException $e) {
                            }
                        }
                    }
                }
            }
        }
        return [$text, $json];
    }

}
