<?php

namespace App\Api\Controller\PostsV3;

use App\Common\ResponseCode;
use App\Models\Post;
use App\Models\Thread;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Illuminate\Support\Arr;

class PositionPostsController extends DzqController
{
    protected $post_ids;
    protected $postId;
    protected $pageSize;
    protected $is_reply;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $filters = $this->inPut('filter') ?: [];
        $threadId = Arr::get($filters, 'threadId');
        $this->postId = Arr::get($filters, 'postId');
        $this->pageSize = Arr::get($filters, 'pageSize');
        $this->is_reply = 0;

        if (empty($threadId) || empty($this->postId)) {
            return false;
        }

        $thread = Thread::query()
            ->where(['id' => $threadId])
            ->whereNull('deleted_at')
            ->first();

        $posts = Post::query()->find($this->postId);
        if(empty($posts)){
            $this->outPut(ResponseCode::RESOURCE_NOT_FOUND);
        }
        // 如果有评论id，说明是楼中楼评论
        if(!empty($posts->reply_post_id))       $this->is_reply = 1;
        if(empty($posts->reply_post_id) && empty($this->pageSize)){
            $this->outPut(ResponseCode::INVALID_PARAMETER, '一级评论缺少pageSize');
        }

        $where = [
            'thread_id' => $threadId,
            'is_first' => 0
        ];
        if($this->is_reply)     $where['reply_post_id'] = $posts->reply_post_id;

        $this->post_ids = Post::query()
            ->where($where)
            ->whereNull('deleted_at')
            ->orderBy('id','asc')
            ->pluck('id')->toArray();


        return $userRepo->canViewThreadDetail($this->user, $thread);
    }

    public function main(){
        $key = array_search($this->postId, $this->post_ids);
        $position = $key + 1;
        $page = 1;
        //这里判断，如果是一级评论，就需要分页，如果不是一级评论则不需要分页，获取的 position 就是评论位置
        if($this->is_reply){
            $location = $position;
        }else{
            $page = ceil(count($this->post_ids)/$this->pageSize);
            $location = $position%$this->pageSize;
        }

        $res_data = [
            'page'  =>  $page,
            'location'  =>  $location,
            'pageSize'  =>  $this->pageSize
        ];
        $this->outPut(ResponseCode::SUCCESS, '获取位置成功', $res_data);
    }

}
