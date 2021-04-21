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


use App\Api\Controller\ThreadsV3\DeleteTomController;
use App\Api\Controller\ThreadsV3\SelectTomController;
use App\Api\Controller\ThreadsV3\UpdateTomController;
use App\Common\ResponseCode;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Thread;
use App\Models\ThreadTom;
use App\Models\User;
use Discuz\Common\Utils;

trait TomTrait
{

    private $CREATE_FUNC = 'create';
    private $DELETE_FUNC = 'delete';
    private $UPDATE_FUNC = 'update';
    private $SELECT_FUNC = 'select';


    private $CLOSE_BUSI_PERMISSION = true;

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
        if (isset($tomContent['indexes'])) {
            $indexes = $tomContent['indexes'];
        } else {
            $indexes = $tomContent;
        }
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
            $this->busiPermission($this->user, $v);
            if (isset($v['tomId']) && isset($v['operation'])) {
                if (in_array($v['operation'], [$this->CREATE_FUNC, $this->DELETE_FUNC, $this->UPDATE_FUNC, $this->SELECT_FUNC])) {
                    $tomId = $v['tomId'];
                    $op = $v['operation'];
                    $body = $v['body'];
                    if (isset($config[$tomId])) {
                        try {
                            $service = new \ReflectionClass($config[$tomId]['service']);
                            if (empty($v['threadId'])) {
                                $service = $service->newInstanceArgs([$this->user, $threadId, $tomId, $k, $op, $body]);
                            } else {
                                $service = $service->newInstanceArgs([$this->user, $v['threadId'], $tomId, $k, $op, $body]);
                            }
                            method_exists($service, $op) && $tomJsons[$k] = $service->$op();
                        } catch (\ReflectionException $e) {
                            Utils::outPut(ResponseCode::INTERNAL_ERROR, $e->getMessage());
                        }
                    }
                }
            }
        }
        return $tomJsons;
    }

    private function busiPermission(User $user, $tom)
    {
        if ($this->CLOSE_BUSI_PERMISSION) {
            return true;
        }
        if ($user->isAdmin()) {
            return true;
        }
        if (!empty($tom['operation']) && $tom['operation'] == $this->CREATE_FUNC) {
            $tomConfig = TomConfig::$map[$tom['tomId']];
            $permissions = Permission::getUserPermissions($this->user);
            if (!in_array($tomConfig['authorize'], $permissions)) {
                Utils::outPut(ResponseCode::UNAUTHORIZED, sprintf('没有插入【%s】权限', $tomConfig['desc']));
            }
        }
        return true;
    }


    private function buildTomJson($threadId, $tomId, $operation, $body)
    {
        return [
            'threadId' => $threadId,
            'tomId' => $tomId,
            'operation' => $operation,
            'body' => $body
        ];
    }

    /**
     * @desc 创建新贴权限
     * @param User $user
     * @param $categoryId
     * @return bool
     */
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

    /**
     * @desc 阅读帖子详情权限
     * @param $user
     * @param $thread
     * @return bool
     */
    private function canViewThreadDetail($user, $thread)
    {
        if ($user->isAdmin() || $user->id == $thread['user_id']) {
            return true;
        }
        $permissions = Permission::getUserPermissions($user);
        $permission = 'category' . $thread['category_id'] . '.thread.viewPosts';
        if (in_array('thread.viewPosts', $permissions) || in_array($permission, $permissions)) {
            return true;
        }
        return false;
    }

    /**
     * @desc 编辑更新帖子权限
     * @param User $user
     * @param $categoryId
     * @param null $threadUserId
     * @return bool
     */
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

    /**
     * @desc 删除帖子的权限
     * @param User $user
     * @param $categoryId
     * @param null $threadUserId
     * @return bool
     */
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

    public function beforeMain($name, $arguments)
    {
        $class = get_called_class();
        if (in_array($class, [UpdateTomController::class, DeleteTomController::class, SelectTomController::class])) {
            $threadId = $this->inPut('threadId');
            $thread = Thread::getOneActiveThread($threadId);
            $user = $arguments[0];
            if (empty($thread)) {
                $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
            }
            if ($class == SelectTomController::class) {
                if (!$this->canViewThreadDetail($user, $thread)) {
                    $this->outPut(ResponseCode::UNAUTHORIZED);
                }
            } else {
                if (!$this->canEditThread($user, $thread->category_id, $thread->user_id)) {
                    $this->outPut(ResponseCode::UNAUTHORIZED);
                }
            }
        }
    }
}
