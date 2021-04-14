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
use Discuz\Http\DiscuzResponseFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @method  create()
 * @method  delete()
 * @method  update()
 * @method  select()
 * @method  userfunc()
 */
abstract class TomBaseBusi
{
    public $tomId = null;
    public $operation = null;
    public $body = [];
    private $permissions = [];
    public $threadId = null;

    public function __construct($threadId, $tomId, $operation, $body)
    {
        $this->operation = $operation;
        $this->body = $body;
        $this->tomId = $tomId;
        $this->threadId = $threadId;
        $this->operationValid();
        //todo 查询用户组的权限数组
    }

    private function operationValid()
    {
        if (!method_exists($this, $this->operation)) {
            throw new \Exception(sprintf('operation [%s] not exist in [%s]', $this->operation, static::class));
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
        if (empty($msg)) {
            if (ResponseCode::$codeMap[$code]) {
                $msg = ResponseCode::$codeMap[$code];
            }
        }
        $data = [
            'Code' => $code,
            'Message' => $msg,
            'Data' => $data,
            'RequestId' => Str::uuid(),
            'RequestTime' => date('Y-m-d H:i:s')
        ];
        $crossHeaders = DiscuzResponseFactory::getCrossHeaders();
        foreach ($crossHeaders as $k => $v) {
            header($k . ':' . $v);
        }
        header('Content-Type:application/json; charset=utf-8', true, 200);
        exit(json_encode($data, 256));
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

}
