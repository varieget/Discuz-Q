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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $group_id
 * @property string $permission
 */
class Permission extends Model
{
    const DEFAULT_PERMISSION = [
        'thread.favorite',              // 收藏
        'thread.likePosts',             // 点赞
        'userFollow.create',            // 关注
        'user.view',                    // 查看个人信息，目前仅用于前台显示权限
        'order.create',                 // 创建订单
        'trade.pay.order',              // 支付订单
        'cash.create',                  // 提现
    ];


    const THREAD_PERMISSION = [
        'switch.createThread',           //开启/允许发布帖子
        'switch.insertImage' ,          //开启/允许插入图片
        'switch.insertVideo',             //开启/允许发布视频
        'switch.insertAudio',            //开启/允许发布语音
        'switch.insertDoc',             //开启/允许发布附件
        'switch.insertGoods',           //开启/允许发布商品
        'switch.insertPay',            //开启/允许发布付费
        'switch.insertReward',          //开启/允许发布悬赏
        'switch.insertRedPacket',         //开启/允许发布红包
        'switch.insertPosition',         //开启/允许发布位置
    ];




    /**
     * {@inheritdoc}
     */
    protected $table = 'group_permission';

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['group_id', 'permission'];

    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * Define the relationship with the group that this permission is for.
     *
     * @return BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public static function categoryPermissions($groupIds = [])
    {
        $permissions = Permission::query()->whereIn('group_id', $groupIds)->get()->toArray();
        $permissions = array_column($permissions, 'permission');
        return $permissions;
    }

    public static function getUserPermissions($user)
    {
        if (app()->has('ASpnWrv4SX')) {
            return app()->get('ASpnWrv4SX');
        } else {
            $groups = $user->groups->toArray();
            $groupIds = array_column($groups, 'id');
            $permissions = Permission::categoryPermissions($groupIds);
            app()->instance('ASpnWrv4SX', $permissions);
            return $permissions;
        }
    }
}
