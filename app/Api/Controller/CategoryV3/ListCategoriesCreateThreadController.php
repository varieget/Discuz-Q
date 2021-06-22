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

namespace App\Api\Controller\CategoryV3;

use App\Common\ResponseCode;
use App\Models\Category;
use App\Repositories\UserRepository;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;

class ListCategoriesCreateThreadController extends DzqController
{
    private $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if ($this->user->isGuest()) {
            throw new PermissionDeniedException();
        }
        return true;
    }

    public function main()
    {
        $categories = Category::query()
            ->select([
                'id as pid', 'name', 'description', 'icon', 'thread_count as threadCount', 'parentid'
            ])
            ->orderBy('parentid', 'asc')
            ->orderBy('sort')
            ->get()->toArray();

        $categoriesFather = [];
        $categoriesChild = [];

        foreach ($categories as $category) {
            if ($this->userRepo->canCreateThread($this->user, $category['pid'])) {
                if ($category['parentid'] !== 0) {
                    $categoriesChild[$category['parentid']][] = $category;
                } else {
                    $categoriesFather[] = $category;
                }
            }
        }

        // 获取一级分类的二级子类
        foreach ($categoriesFather as $key => $value) {
            if (isset($categoriesChild[$value['pid']])) {
                $categoriesFather[$key]['children'] = $categoriesChild[$value['pid']];
            } else {
                $categoriesFather[$key]['children'] = [];
            }
        }

        if (empty($categoriesFather)) {
            $this->outPut(ResponseCode::SUCCESS, '您没有发帖权限');
        }
        $this->outPut(ResponseCode::SUCCESS, '', $categoriesFather);
    }
}
