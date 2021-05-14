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

namespace App\Api\Controller\ThreadsV3;

use App\Commands\Thread\EditThread;
use App\Common\ResponseCode;
use App\Models\Thread;
use App\Repositories\UserRepository;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Illuminate\Contracts\Bus\Dispatcher;

class OperateThreadController extends DzqController
{
    use AssertPermissionTrait;

    protected $bus;

    public $providers = [
    ];


    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    // 权限检查
    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $actor = $this->user;
        $isSticky = $this->inPut('isSticky');
        $isEssence = $this->inPut('isEssence');
        $isFavorite = $this->inPut('isFavorite');

        if ($actor->isGuest()) {
            throw new PermissionDeniedException('没有权限');
        }
        if (
            isset($isSticky)
            && !$userRepo->canStickThread($actor)
        ) {
            throw new PermissionDeniedException('没有置顶权限');
        }
        if (
            isset($isEssence)
            && !$userRepo->canEssenceThread($actor)
        ) {
            throw new PermissionDeniedException('没有加精权限');
        }
        if (
            isset($isFavorite)
            && !$userRepo->canFavoriteThread($actor)
        ) {
            throw new PermissionDeniedException('没有收藏权限');
        }

        return true;
    }

    public function main()
    {
        //参数校验
        $thread_id= $this->inPut('id');
        if(empty($thread_id))     return  $this->outPut(ResponseCode::INVALID_PARAMETER);

        $threadRow = Thread::query()->where('id',$thread_id)->first();
        if(empty($threadRow)){
            return  $this->outPut(ResponseCode::INVALID_PARAMETER,"主题id".$thread_id."不存在");
        }

        $categoriesId = $this->inPut('categoriesId');
        $type = $this->inPut('type');

        //当传分类时有默认
        $isEssence = $this->inPut('isEssence');
        $isSticky = $this->inPut('isSticky');
        $isFavorite = $this->inPut('isFavorite');

        $attributes = [];
        $requestData = [];
        if($categoriesId){
            $requestData = [
                "type" => "threads",
                "relationships" =>  [
                    "category" =>  [
                        "data" =>  [
                            "type" => "categories",
                            "id" => $categoriesId
                        ]
                    ],
                ]
            ];
            $attributes['type'] = (string)$type;
        }

        if($isEssence || $isEssence===false){
            $attributes['isEssence'] = $isEssence;
        }
        if($isSticky || $isSticky===false){
            $attributes['isSticky'] = $isSticky;
        }
        if($isFavorite || $isFavorite===false){
            $attributes['isFavorite'] = $isFavorite;
        }

        $requestData['id'] = $thread_id;
        $requestData['type'] = 'threads';

        $requestData['attributes'] = $attributes;
        $result = $this->bus->dispatch(
            new EditThread($thread_id, $this->user, $requestData)
        );
        $result = $this->camelData($result);

        return $this->outPut(ResponseCode::SUCCESS,'', $result);

    }


}
