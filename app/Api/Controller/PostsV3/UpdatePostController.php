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

namespace App\Api\Controller\PostsV3;

use App\Api\Serializer\PostSerializer;
use App\Commands\Post\EditPost;
use App\Common\CacheKey;
use App\Common\ResponseCode;
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadUser;
use App\Repositories\UserRepository;
use Discuz\Base\DzqCache;
use Discuz\Base\DzqController;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;

class UpdatePostController extends DzqController
{
    protected $bus;

    protected $postSerializer;

    public function __construct(
        PostSerializer $postSerializer,
        Dispatcher $bus
    )
    {
        $this->postSerializer = $postSerializer;
        $this->bus = $bus;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {

        $post = Post::query()->where(['id' => $this->inPut('pid')])->first();
        if (!$post) {
            return false;
        }

        $data = $this->inPut('data', []);
        $data = Arr::get($data, 'attributes', []);

        // TODO 暂时改为只有管理员可审核和编辑
        if (isset($data['content']) || (isset($data['isApproved']) && $data['isApproved'] < 3)) {
            return $this->user->isAdmin();
        }
        if (isset($attributes['isDeleted'])) {
            return $userRepo->canHidePost($this->user, $post);
        }

        return true;
    }

    public function main()
    {
        $actor = $this->user;
        $postId = $this->inPut('pid');
        if (empty($postId)) return $this->outPut(ResponseCode::INVALID_PARAMETER);

        $data = $this->inPut('data', []);

        if (empty($data)) return $this->outPut(ResponseCode::INVALID_PARAMETER);

        $post = $this->bus->dispatch(
            new EditPost($postId, $actor, $data)
        );

        $threadId = $post['thread_id'];

        $isFavorite = ThreadUser::query()->where('thread_id', $threadId)->where('user_id', $actor->id)->exists();
        $thread = Thread::query()->where("id", $threadId)->first(["rewarded_count", "paid_count"]);

        $content = "";
        if (!empty($data['attributes']['content'])) {
            $content = $data['attributes']['content'];
        }
        $build = [
            'pid' => $postId,
            'threadId' => $threadId,
            'content' => str_replace(['<t><p>', '</p></t>'], ['', ''], $content),
            'likeCount' => $post['like_count'],
            'likePayCount' => $post['like_count'] + $thread['rewarded_count'] + $thread['paid_count'],
            'replyCount' => $post['reply_count'],
            'isFirst' => $post['is_first'],
            'isApproved' => $post['is_approved'],
            'updatedAt' => optional($post['updated_at'])->format('Y-m-d H:i:s'),
            'isLiked' => $data['attributes']['isLiked'],
            'canLike' => $this->user->can('like', $post),
            'canFavorite' => (bool)$this->user->can('favorite', $post),
            'isFavorite' => $isFavorite,
            'rewards' => floatval(sprintf('%.2f', $post->getPostReward())),
            'redPacketAmount' => $this->postSerializer->getPostRedPacketAmount($post['id'], $post['thread_id'], $post['user_id']),
        ];
        if ($post->id == $postId) {
            return $this->outPut(ResponseCode::SUCCESS, '', $build);
        }

        return $this->outPut(ResponseCode::NET_ERROR, '', []);
    }

    public function clearCache($user)
    {
        $postId = $this->inPut('pid');
        DzqCache::del2HashKey(CacheKey::LIST_THREADS_V3_POST_LIKED, $user->id, $postId);
        DzqCache::delKey(CacheKey::LIST_THREADS_V3_COMPLEX);
        $post = Post::query()->where('id', $postId)->first();
        if (!empty($post)) {
            $threadId = $post['thread_id'];
            DzqCache::delHashKey(CacheKey::LIST_THREADS_V3_THREADS, $threadId);
            DzqCache::delHashKey(CacheKey::LIST_THREADS_V3_POSTS, $threadId);
            DzqCache::delHashKey(CacheKey::LIST_THREADS_V3_POST_USERS, $threadId);
        }
    }

}
