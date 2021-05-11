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

namespace App\Repositories;

use App\Common\PermissionKey;
use App\Models\Group;
use App\Models\User;
use Discuz\Foundation\AbstractRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserRepository extends AbstractRepository
{
    /**
     * Get a new query builder for the users table.
     *
     * @return Builder
     */
    public function query()
    {
        return User::query();
    }

    /**
     * Find a user by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param int $id
     * @param User $actor
     * @return Builder|\Illuminate\Database\Eloquent\Model|User
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        $query = User::where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * Find a user by an identification (username or phone number).
     *
     * @param array $param
     * @return User|null
     */
    public function findByIdentification($param)
    {
        return User::where($param)->first();
    }

    private function checkCategoryPermission(User $user, string $ability, $categoryId = null)
    {
        $abilities = [$ability];

        if ($categoryId) {
            $abilities[] = 'category'.$categoryId.'.'.$ability;
        }

        return $user->hasPermission('switch.'.$ability) && $user->hasPermission($abilities, false);
    }

    public function canCreateThread(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::CREATE_THREAD, $categoryId);
    }

    public function canInsertImage(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::THREAD_INSERT_IMAGE, $categoryId);
    }

    public function canInsertVideo(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::THREAD_INSERT_VIDEO, $categoryId);
    }

    public function canInsertAudio(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::THREAD_INSERT_AUDIO, $categoryId);
    }

    public function canInsertAttachment(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::THREAD_INSERT_ATTACHMENT, $categoryId);
    }

    public function canInsertGoods(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::THREAD_INSERT_GOODS, $categoryId);
    }

    public function canInsertPay(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::THREAD_INSERT_PAY, $categoryId);
    }

    public function canInsertReward(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::THREAD_INSERT_REWARD, $categoryId);
    }

    public function canInsertRedPacket(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::THREAD_INSERT_RED_PACKET, $categoryId);
    }

    public function canInsertPosition(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::THREAD_INSERT_POSITION, $categoryId);
    }

    public function canAllowAnonymous(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::THREAD_ALLOW_ANONYMOUS, $categoryId);
    }

    public function canViewThreads(User $user, $categoryId = null)
    {
        return $this->checkCategoryPermission($user, PermissionKey::VIEW_THREADS, $categoryId);
    }

    public function canCreateOrder(User $user)
    {
        return $user->hasPermission(PermissionKey::ORDER_CREATE);
    }

    public function canHideThread(User $user, $thread, $categoryId = null)
    {
        return ($user->id === $thread->user_id && $this->checkCategoryPermission($user, PermissionKey::OWN_THREAD_DELETE, $categoryId))
            || $this->checkCategoryPermission($user, PermissionKey::THREAD_DELETE, $categoryId);
    }

    public function canViewListWallet(User $user){
        return $user->hasPermission(PermissionKey::WALLET_VIEW_LIST);
    }

    public function canViewListLogs(User $user){
        return $user->hasPermission(PermissionKey::WALLET_LOGS_VIEW_LIST);
    }

    public function canViewListCash(User $user){
        return $user->hasPermission(PermissionKey::CASH_VIEW_LIST);
    }

    public function canDeleteGroup(User $user, Group $group)
    {
        $groups = [
            Group::ADMINISTRATOR_ID,
            Group::BAN_ID,
            Group::UNPAID,
            Group::GUEST_ID,
            Group::MEMBER_ID,
        ];

        return !in_array($group->id, $groups) && $user->isAdmin();
    }
}
