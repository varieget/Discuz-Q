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

namespace App\Modules\ThreadTos;

use Illuminate\Support\Arr;

/**
 * @method  create()
 * @method  delete()
 * @method  update()
 * @method  select()
 * @method  userfunc()
 */
abstract class DzqTosBaseBusi
{
    private $operation = null;
    public $body = [];
    private $permissions = [];

    public function __construct($operation, $body)
    {
        $this->operation = $operation;
        $this->body = $body;
        $this->operationValid();
        //todo 查询用户组的权限数组
    }

    private function operationValid()
    {
        if (!method_exists($this, $this->operation)) {
            throw new \Exception('操作类型[' . $this->operation . ']不存在');
        }
    }

    /**
     * 帖子对象存储获取对象入参
     * @param $key
     * @return array|\ArrayAccess|mixed
     */
    public function getParams($key){
        return Arr::get($this->body,$key);
    }
}
