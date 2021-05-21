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

use App\Common\ResponseCode;
use App\Models\Category;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadTag;
use App\Models\ThreadTom;
use App\Modules\ThreadTom\TomConfig;
use App\Repositories\UserRepository;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;

class CreateThreadController extends DzqController
{
    use ThreadTrait;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $categoryId = $this->inPut('categoryId');
        $user = $this->user;

        if (!$userRepo->canCreateThread($user, $categoryId)) {
            throw new PermissionDeniedException('没有发帖权限');
        }

        $price = floatval($this->inPut('price'));
        $attachmentPrice = floatval($this->inPut('attachmentPrice'));
        if (
            ($price > 0 || $attachmentPrice > 0)
            && !$userRepo->canInsertPayToThread($user)
        ) {
            throw new PermissionDeniedException('没有插入【付费】权限');
        }


        if (
            !empty($this->inPut('position'))
            && !$userRepo->canInsertPositionToThread($user)
        ) {
            throw new PermissionDeniedException('没有插入【位置信息】权限');
        }

        return true;
    }

    public function main()
    {
        $this->limitCreateThread();
        !empty($position) && $this->dzqValidate($position, [
            'longitude' => 'required',
            'latitude' => 'required',
            'address' => 'required',
            'location' => 'required'
        ]);
        $result = $this->createThread();
        $this->outPut(ResponseCode::SUCCESS, '', $result);
    }


    /**
     * @desc 发布一个新帖子
     */
    private function createThread()
    {
        $db = $this->getDB();
        $db->beginTransaction();
        try {
            $result = $this->executeEloquent();
            $db->commit();
            return $result;
        } catch (\Exception $e) {
            $db->rollBack();
            $this->info('createThread_error_' . $this->user->id, $e->getMessage());
            $this->outPut(ResponseCode::DB_ERROR, $e->getMessage());
        }
    }

    private function executeEloquent()
    {
        $content = $this->inPut('content');

        if (!empty($content['text'])) {
            $content['text'] = $this->optimizeEmoji($content['text']);
        }

        //插入thread数据
        $thread = $this->saveThread($content);
        //插入post数据
        $post = $this->savePost($thread, $content);
        //插入话题
        $this->saveTopic($thread, $content);
        //插入tom数据
        $tomJsons = $this->saveTom($thread, $content, $post);
        return $this->getResult($thread, $post, $tomJsons);
    }

    private function saveThread(&$content)
    {
        $thread = new Thread();
        $userId = $this->user->id;
        $categoryId = $this->inPut('categoryId');
        $title = $this->inPut('title');//title没有则自动生成
        $price = $this->inPut('price');
        $attachmentPrice = $this->inPut('attachmentPrice');
        $freeWords = $this->inPut('freeWords');
        $position = $this->inPut('position');
        $isAnonymous = $this->inPut('anonymous');
        $isDraft = $this->inPut('draft');
        if (empty($content)) $this->outPut(ResponseCode::INVALID_PARAMETER, '缺少 content 参数');
        if (empty($categoryId)) $this->outPut(ResponseCode::INVALID_PARAMETER, '缺少 categoryId 参数');
//        empty($title) && $title = Post::autoGenerateTitle($content['text']);//不自动生成title
        $dataThread = [
            'user_id' => $userId,
            'category_id' => $categoryId,
            'title' => $title,
            'post_count' => 1
        ];
        $price = floatval($price);
        $attachmentPrice = floatval($attachmentPrice);
        $freeWords = floatval($freeWords);
        if ($price > 0 || $attachmentPrice > 0) {
            $price > 0 && $dataThread['price'] = $price;
            $attachmentPrice > 0 && $dataThread['attachment_price'] = $attachmentPrice;
            $freeWords > 0 && $dataThread['free_words'] = $freeWords;
        }
        !empty($freeWords) && $dataThread['free_words'] = $freeWords;
        if (!empty($position)) {
            $dataThread['longitude'] = $position['longitude'];
            $dataThread['latitude'] = $position['latitude'];
            $dataThread['address'] = $position['address'];
            $dataThread['location'] = $position['location'];
        } else {
            $dataThread['address'] = '';
            $dataThread['location'] = '';
        }
        [$newTitle, $newContent] = $this->boolApproved($title, $content['text'], $isApproved);
        $content['text'] = $newContent;
        $dataThread['title'] = $newTitle;
        if ($isApproved) {
            $dataThread['is_approved'] = Thread::BOOL_NO;
        } else {
            $dataThread['is_approved'] = Thread::BOOL_YES;
        }
        $isDraft && $dataThread['is_draft'] = Thread::BOOL_YES;
        !empty($isAnonymous) && $dataThread['is_anonymous'] = Thread::BOOL_YES;
        $thread->setRawAttributes($dataThread);
        $thread->save();
        if (!$isApproved && !$isDraft) {
            $this->user->refreshThreadCount();
            $this->user->save();
            Category::refreshThreadCountV3($categoryId);
        }

        return $thread;
    }

    /**
     * @desc  todo 扩展属性权限判断,后面迁移到busi插件里
     * @param $permission
     * @param $msg
     */
    private function propertyExtendPermission($permission, $msg)
    {
        $permissions = Permission::getUserPermissions($this->user);
        if (!in_array($permission, $permissions) && !$this->user->isAdmin()) {
            //todo 联调关闭权限检查
            if (!$this->CLOSE_BUSI_PERMISSION) {
                $this->outPut(ResponseCode::UNAUTHORIZED, $msg);
            }
        }
    }


    private function savePost($thread, $content)
    {
        $text = $content['text'];
        $post = new Post();
        [$ip, $port] = $this->getIpPort();
        $dataPost = [
            'user_id' => $this->user->id,
            'thread_id' => $thread['id'],
            'content' => $text,
            'ip' => $ip,
            'port' => $port,
            'is_first' => Post::FIRST_YES,
            'is_approved' => Post::APPROVED
        ];
        $post->setRawAttributes($dataPost);
        $post->save();
        return $post;
    }

    private function saveTom($thread, $content, $post)
    {
        $indexes = $content['indexes'];
        $attrs = [];
        $tomJsons = $this->tomDispatcher($indexes, null, $thread['id'], $post['id']);
        $tags = [];
        if (!empty($content['text'])) {
            $tags[] = [
                'thread_id' => $thread['id'],
                'tag' => TomConfig::TOM_TEXT,
            ];
        }
        foreach ($tomJsons as $key => $value) {
            $attrs[] = [
                'thread_id' => $thread['id'],
                'tom_type' => $value['tomId'],
                'key' => $key,
                'value' => json_encode($value['body'], 256)
            ];
            $tags[] = [
                'thread_id' => $thread['id'],
                'tag' => $value['tomId']
            ];
        }
        ThreadTom::query()->insert($attrs);
        //添加tag类型
        ThreadTag::query()->insert($tags);
        return $tomJsons;
    }

    private function getResult($thread, $post, $tomJsons)
    {
        $user = $this->user;
        $group = Group::getGroup($user->id);
        return $this->packThreadDetail($user, $group, $thread, $post, $tomJsons, true);
    }

    private function limitCreateThread()
    {
        $threadFirst = Thread::query()
            ->select(['id', 'user_id', 'category_id', 'created_at'])
            ->where('user_id', $this->user->id)
            ->orderByDesc('created_at')->first();
        //发帖间隔时间30s
        if (!empty($threadFirst) && (time() - strtotime($threadFirst['created_at'])) < 30) {
            $this->outPut(ResponseCode::RESOURCE_EXIST, '发帖太快，请稍后重试');
        }
    }
}
