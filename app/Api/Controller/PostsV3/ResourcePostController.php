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


use App\Api\Serializer\AttachmentSerializer;
use App\Api\Serializer\CommentPostSerializer;
use App\Common\ResponseCode;
use App\Models\Post;
use Discuz\Base\DzqController;

class ResourcePostController extends DzqController
{
    public $providers = [
        \App\Providers\PostServiceProvider::class,
    ];

    //返回的数据一定包含的数据
    public $include = [
        'user:id,username,avatar',
        'user.groups:id,name,is_display',
        'likedUsers:id,username,avatar',
    ];


    public function main()
    {
        $coment_post_serialize = $this->app->make(CommentPostSerializer::class);
        $attachment_serialize = $this->app->make(AttachmentSerializer::class);

        $post_id = $this->inPut('pid');
        if(empty($post_id))       return  $this->outPut(ResponseCode::INVALID_PARAMETER );
        $comment_post = Post::find($post_id);

        if(empty($comment_post))          return  $this->outPut(ResponseCode::INVALID_PARAMETER);
        if($comment_post->if_first || $comment_post->is_comment || $comment_post->thread->deleted_at){
            return $this->outPut(ResponseCode::NET_ERROR);
        }
        $include = !empty($this->inPut('include')) ? array_unique(array_merge($this->include, explode(',', $this->inPut('include')))) : $this->include;
        /*  暂时不需要缓存
        $cacheKey = CacheKey::POST_RESOURCE_BY_ID.$post_id;
        $cache = app('cache');
        $cacheData = $cache->get($cacheKey);
        if(!empty($cacheData)){
            $cacheRet = unserialize($cacheData);
            return $this->outPut(ResponseCode::SUCCESS,'', $cacheRet);
        }
        */
        $comment_post->loadMissing($include);
        Post::setStateUser($this->user);

        $data = $coment_post_serialize->getDefaultAttributes($comment_post, $this->user);
        $data['canLike'] = $this->user->can('like', $comment_post);
        if($likeState = $comment_post->likeState){
            $data['isLiked'] = true;
            $data['likedAt'] = $this->formatDate($likeState->created_at);
        }else{
            $data['isLiked'] = false;
        }
        $data['images'] = [];
        $data['likeUsers'] = $comment_post->likedUsers;
        if(!empty($comment_post->images)){
            foreach ($comment_post->images as $key => $val){
                $data['images'][$key] = $attachment_serialize->getDefaultAttributes($val, $this->user);
                $data['images'][$key]['typeId'] = $data['images'][$key]['type_id'];
                unset($data['images'][$key]['type_id']);
            }
        }
        //获取回复评论列表
        if(intval($data['replyCount']) > 0){
            $replyId = Post::query()
                ->where('reply_post_id',$post_id)
                ->where('is_comment', true)
                ->pluck("id");
            $replyIdArr = $replyId->toArray();
            foreach ($replyIdArr as $k=>$value){
                $comment_post = Post::query()->where('id',$value)->first();
                $data['commentPosts'][$k] = $coment_post_serialize->getDefaultAttributes($comment_post);
            }
        }
//        $cache->put($cacheKey, serialize($data), 5*60);
        return $this->outPut(ResponseCode::SUCCESS,'', $data);

    }




}
