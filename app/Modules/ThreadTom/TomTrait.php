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


use App\Models\Permission;
use App\Models\ThreadTom;
use App\Models\User;

trait TomTrait
{

    private $CREATE_FUNC = 'create';
    private $DELETE_FUNC = 'delete';
    private $UPDATE_FUNC = 'update';
    private $SELECT_FUNC = 'select';


    /**
     * @desc 支持一次提交包含新建或者更新或者删除等各种类型混合
     * @param $tomContent
     * @param null $operation
     * @param null $threadId
     * @return array
     */
    private function tomDispatcher($tomContent, $operation = null, $threadId = null)
    {
        $config = TomConfig::$map;
        $tomJsons = [];
        $indexes = $tomContent['indexes'];
        foreach ($indexes as $k => $v) {
            !empty($operation) && $v['operation'] = $operation;
            if (!isset($v['operation'])) {
                if (empty($v['body'])) {
                    $v['operation'] = $this->DELETE_FUNC;
                } else {//create/update
                    if (empty($threadId)) {
                        $v['operation'] = $this->CREATE_FUNC;
                    } else {
                        $exist = ThreadTom::query()->where(['thread_id' => $threadId, 'tom_type' => $v['tomId'], 'key' => $k, 'status' => ThreadTom::STATUS_ACTIVE])->exists();
                        if ($exist) {
                            $v['operation'] = $this->UPDATE_FUNC;
                        } else {
                            $v['operation'] = $this->CREATE_FUNC;
                        }
                    }
                }
            }
            if (isset($v['tomId']) && isset($v['operation']) && isset($v['body'])) {
                if (in_array($v['operation'], [$this->CREATE_FUNC, $this->DELETE_FUNC, $this->UPDATE_FUNC, $this->SELECT_FUNC])) {
                    $tomId = $v['tomId'];
                    $op = $v['operation'];
                    $body = $v['body'];
                    if (isset($config[$tomId])) {
                        try {
                            $service = new \ReflectionClass($config[$tomId]['service']);
                            $service = $service->newInstanceArgs([$tomId, $op, $body]);
                            method_exists($service, $op) && $tomJsons[$k] = $service->$op();
                        } catch (\ReflectionException $e) {
                            dd($e->getMessage());
                        }
                    }
                }
            }
        }
        return $tomJsons;
    }

    private function canCreateThread(User $user, $categoryId)
    {
        if ($user->isAdmin()) {
            return true;
        }
        $permissions = Permission::getUserPermissions($user);
        $permission = 'category' . $categoryId . '.createThread';
        if (in_array('createThread', $permissions) || in_array($permission, $permissions)) {
            return true;
        }
        return false;
    }

    private function canViewThreadDetail($user, $categoryId)
    {
        if ($user->isAdmin()) {
            return true;
        }
        $permissions = Permission::getUserPermissions($user);
        $permission = 'category' . $categoryId . '.thread.viewPosts';
        if (in_array('thread.viewPosts', $permissions) || in_array($permission, $permissions)) {
            return true;
        }
        //todo 免费查看付费贴、付费图片、付费语音、付费问答
        return false;
    }

    private function canViewThread($user, $categoryId)
    {
        return true;
//        if ($user->isAdmin()) {
//            return true;
//        }
//        $permissions = Permission::getUserPermissions($user);
//        $permission = 'category' . $categoryId . '.thread.viewPosts';
//        if (in_array('thread.viewPosts', $permissions) || in_array($permission, $permissions)) {
//            return true;
//        }
//        //todo 免费查看付费贴、付费图片、付费语音、付费问答
//        return false;
    }

    private function canEditThread(User $user, $categoryId, $threadUserId = null)
    {
        if ($user->isAdmin()) {
            return true;
        }
        $permissions = Permission::getUserPermissions($user);
        $permission = 'category' . $categoryId . '.thread.edit';
        if (in_array('thread.edit', $permissions) || in_array($permission, $permissions)) {
            return true;
        }
        if (!empty($threadUserId) && $user->id == $threadUserId) {
            $permission = 'category' . $categoryId . '.thread.editOwnThreadOrPost';
            if (in_array('thread.editOwnThreadOrPost', $permissions) || in_array($permission, $permissions)) {
                return true;
            }
        }
    }

    private function canDeleteThread(User $user, $categoryId, $threadUserId = null)
    {
        if ($user->isAdmin()) {
            return true;
        }
        $permissions = Permission::getUserPermissions($user);
        $permission = 'category' . $categoryId . '.thread.hide';
        if (in_array('thread.hide', $permissions) || in_array($permission, $permissions)) {
            return true;
        }
        if (!empty($threadUserId) && $user->id == $threadUserId) {
            $permission = 'category' . $categoryId . '.thread.hideOwnThreadOrPost';
            if (in_array('thread.hideOwnThreadOrPost', $permissions) || in_array($permission, $permissions)) {
                return true;
            }
        }
        return false;
    }

    private function canUpdateTom()
    {
        return true;
    }

    private function canDeleteTom()
    {
        return true;
    }

}
