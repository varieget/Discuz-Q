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

use App\Common\ResponseCode;
use App\Models\ThreadTom;
use App\Models\User;
use Discuz\Common\Utils;
use Discuz\Http\DiscuzResponseFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @method  create()
 * @method  update()
 * @method  select()
 * @method  userfunc()
 */
abstract class TomBaseBusi
{
    /**
     * 是否需要支付
     */
    public const NEED_PAY = 0;

    public $tomId = null;
    public $operation = null;
    public $body = [];
    public $permissions = [];
    public $threadId = null;
    public $postId = null;
    public $user = null;
    public $key = null;
    public $app = null;

    public $canViewTom = true;

    public function __construct(User $user, $threadId, $postId, $tomId, $key, $operation, $body, $canViewTom)
    {
        $this->app = app();
        $this->operation = $operation;
        $this->body = $body;
        $this->tomId = $tomId;
        $this->threadId = $threadId;
        $this->postId = $postId;
        $this->user = $user;
        $this->key = $key;
        $this->canViewTom = $canViewTom;
        $this->operationValid();
    }

    private function operationValid()
    {
        if (!method_exists($this, $this->operation)) {
            $this->outPut(ResponseCode::INTERNAL_ERROR, sprintf('operation [%s] not exist in [%s]', $this->operation, static::class));
        }
    }

    /**
     * @desc 帖子对象存储获取对象入参
     * @param $key
     * @return array|\ArrayAccess|mixed
     */
    public function getParams($key)
    {
        return Arr::get($this->body, $key);
    }

    /**
     * @desc输出结果写入到thread_tom表的value值
     * @param $array
     * @return array
     */
    public function jsonReturn($array)
    {
        $ret = [
            'tomId' => $this->tomId,
            'operation' => $this->operation,
            'body' => $array
        ];
        if (!empty($this->threadId)) {
            $ret['threadId'] = $this->threadId;
        }
        return $ret;
    }

    /*
     * 接口出参
     */
    public function outPut($code, $msg = '', $data = [])
    {
        Utils::outPut($code, $msg, $data, Str::uuid(), date('Y-m-d H:i:s'));
    }

    /*
     * 入参判断
     */
    public function dzqValidate($inputArray, array $rules, array $messages = [], array $customAttributes = [])
    {
        try {
            $validate = app('validator');
            $validate->validate($inputArray, $rules);
        } catch (\Exception $e) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, $e->getMessage());
        }
    }

    public function camelData($arr, $ucfirst = false)
    {
        if (is_object($arr) && is_callable([$arr, 'toArray'])) $arr = $arr->toArray();
        if (!is_array($arr)) {
            //如果非数组原样返回
            return $arr;
        }
        $temp = [];
        foreach ($arr as $key => $value) {
            $key1 = Str::camel($key);           // foo_bar  --->  fooBar
            if ($ucfirst) $key1 = Str::ucfirst($key1);
            $value1 = self::camelData($value);
            $temp[$key1] = $value1;
        }
        return $temp;
    }

    public function delete()
    {
        ThreadTom::deleteTom($this->threadId, $this->tomId, $this->key);
        return $this->jsonReturn(false);
    }
}
