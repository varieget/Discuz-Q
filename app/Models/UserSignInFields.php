<?php
/**
 * Copyright (C) 2020 Tencent Cloud.
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

namespace App\Models;

use Discuz\Models\DzqModel;

/**
 * @property int $id
 * @property int $aid
 * @property int $user_id
 * @property string $fields_ext
 * @property string $remark
 * @property int $status
 * */
class UserSignInFields extends DzqModel
{
    protected $table = 'user_sign_in_fields';

    const STATUS_DELETE = 0; //废弃
    const STATUS_AUDIT = 1;//待审核
    const STATUS_REJECT = 2;//已驳回
    const STATUS_PASS = 3;//审核通过
    private $userId = 10;
    public function getUserSignInFields($userId)
    {
        if (empty($userId)) $userId = $this->userId;
        $adminSignIn = AdminSignInFields::instance()->getAdminSignInFields();
        $userSignIn = self::query()
            ->select(['id', 'aid', 'user_id', 'fields_ext', 'remark', 'status'])
            ->where('user_id', $userId)
            ->where('status', '!=', self::STATUS_DELETE)
            ->get()->toArray();
        $userSignIn = array_column($userSignIn, null, 'aid');
        $result = [];
        foreach ($adminSignIn as $item) {
            $data = [
                'aid' => $item['id'],
                'name' => $item['name'],
                'type' => $item['type'],
                'fields_desc' => $item['fields_desc'],
                'type_desc' => $item['type_desc'],
            ];
            if (isset($userSignIn[$item['id']])) {
                $data['id']=$userSignIn[$item['id']]['id'];
                $data['fields_ext'] = $userSignIn[$item['id']]['fields_ext'];
                $data['remark'] = $userSignIn[$item['id']]['remark'];
                $data['status'] = $userSignIn[$item['id']]['status'];
            } else {
                $data['id']='';
                $data['fields_ext'] = $item['fields_ext'];
                $data['remark'] = '';
                $data['status'] = self::STATUS_AUDIT;
            }
            $result[] = $data;
        }
        return $result;
    }

    /**
     *用户新建或编辑扩展字段内容
     * @param $userId
     * @param $attributes
     * @return bool
     */
    public function userSaveUserSignInFields($userId, $attributes)
    {
        if (empty($userId)) $userId = $this->userId;
        foreach ($attributes as $attribute) {
            if (!empty($attribute['id'])) {//更新
                $userSignIn = self::query()->where('id', $attribute['id'])
                    ->where('status','!=',self::STATUS_DELETE)
                    ->where('user_id',$userId)
                    ->first();
                if (empty($userSignIn)) {
                    continue;
                }
                if ($userSignIn['status'] == self::STATUS_REJECT) {
                    $userSignIn->setAttribute('status', self::STATUS_DELETE);
                    $userSignIn->save();
                    $userSignIn = new UserSignInFields();
                }
                $rawData = [
                    'aid' => $attribute['aid'],
                    'user_id' => $userId,
                    'fields_ext' => $attribute['fields_ext'],
                    'status' => self::STATUS_AUDIT,
                ];
                $userSignIn->setRawAttributes($rawData);
                $userSignIn->save();
            } else {//新建
                //todo 不能重复添加 by coralchu
                $userSignIn = new UserSignInFields();
                $rawData = [
                    'aid' => $attribute['aid'],
                    'user_id' => $userId,
                    'fields_ext' => $attribute['fields_ext'],
                    'status' => self::STATUS_AUDIT,
                ];
                $userSignIn->setRawAttributes($rawData);
                $userSignIn->save();
            }
        }
        return true;
    }

    /**
     *管理员审核扩展信息
     * @param $userId
     * @param $attributes
     * @return bool
     */
    public function adminSaveUserSignInFields($userId, $attributes)
    {
        if (empty($userId)) $userId = $this->userId;
        $isAuditPass = true;
        foreach ($attributes as $attribute) {
            $userSignIn = self::query()->where('id', $attribute['id'])
                ->where('user_id', $userId)
                ->first();
            if (empty($userSignIn)) {
                continue;
            }
            $rawData = [
                'aid' => $attribute['aid'],
                'user_id' => $userId,
                'fields_ext' => $attribute['fields_ext'],
                'remark' => $attribute['remark'],
                'status' => $attribute['status'],
            ];
            $attribute['status'] != self::STATUS_PASS && $isAuditPass = false;
            $userSignIn->setRawAttributes($rawData);
            if (!$userSignIn->save()) {
                $isAuditPass = false;
            }
        }
        if ($isAuditPass) {
            $user = User::query()->where('id', $userId)->get()->first();
            $user->status = User::STATUS_NORMAL;
            $user->save();
        }
        return true;
    }

}
