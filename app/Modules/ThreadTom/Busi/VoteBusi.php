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

namespace App\Modules\ThreadTom\Busi;

use App\Common\CacheKey;
use App\Models\ThreadVote;
use App\Models\ThreadVoteSubitem;
use Carbon\Carbon;
use Discuz\Base\DzqCache;
use App\Common\ResponseCode;
use App\Modules\ThreadTom\TomBaseBusi;
use TencentCloud\Iotcloud\V20180614\Models\ProductResourceInfo;

class VoteBusi extends TomBaseBusi
{
    const SUBITEMS_LENGTH = 50;

    public function create()
    {
        $input = $this->verification();
        $this->db->beginTransaction();
        // 先创建 thread_vote
        $thread_vote = ThreadVote::query()->create([
            'thread_id'    =>  $this->threadId,
            'vote_title'    =>  $input['vote_title'],
            'choice_type'    =>  $input['choice_type']
        ]);
        if($thread_vote === false){
            $this->db->rollBack();
            $this->outPut(ResponseCode::INVALID_PARAMETER, '新增投票帖失败');
        }
        // 再创建 thread_vote_subitems
        $thread_vote_insert = [];
        foreach ($input['subitems'] as $val){
            $thread_vote_insert[] = [
                'thread_vote_id'    =>  $thread_vote->id,
                'content'           =>  $val['content'],
                'created_at'        =>  $thread_vote->created_at
            ];
        }
        $res = $this->db->table('thread_vote_subitems')->insert($thread_vote_insert);
        if($res === false){
            $this->db->rollBack();
            $this->outPut(ResponseCode::INVALID_PARAMETER, '新增投票帖失败');
        }
        $this->db->commit();
        //这里的格式暂时作为数组的形式存，方便以后一个帖子多个投票的时候扩展
        return $this->jsonReturn(['voteIds' => [$thread_vote->id]]);
    }

    public function update()
    {
        $input = $this->updateCheckVar();
        $this->db->beginTransaction();
        $thread_vote = ThreadVote::query()->find($input['vote_id']);
        if(empty($thread_vote)){
            $this->outPut(ResponseCode::INVALID_PARAMETER, '投票信息不存在');
        }
        //先修改 thread_vote
        $thread_vote->vote_title = $input['vote_title'];
        $thread_vote->choice_type = $input['choice_type'];
        $res = $thread_vote->save();
        if($res === false){
            $this->db->rollBack();
            $this->outPut(ResponseCode::INTERNAL_ERROR, '修改投票信息出错');
        }
        //在修改 thread_vote_subitems
        $thread_vote_subitmes_ids = array_column($input['subitems'], 'id');
        //找出之前的 thread_vote_subitems 数据
        $thread_vote_subitems_old_ids = ThreadVoteSubitem::query()->where('thread_vote_id', $thread_vote->id)->pluck('id')->toArray();
        $remove_sub_ids = array_diff($thread_vote_subitems_old_ids, $thread_vote_subitmes_ids);
        //删除这次没有传对应id过来的
        $res = ThreadVoteSubitem::query()->whereIn('id', $remove_sub_ids)->delete();
        if($res === false){
            $this->db->rollBack();
            $this->outPut(ResponseCode::INTERNAL_ERROR, '修改投票选项出错');
        }
        //修改
        foreach ($input['subitems'] as $val){
            $insert_sub = [];
            if(!empty($val)){
                $res = ThreadVoteSubitem::query()->where('id', $val['id'])->update(['content' => $val['content']]);
            }else{
                $insert_sub[] = [
                    'thread_vote_id'    =>  $thread_vote->id,
                    'content'           =>  $val['content'],
                    'created_at'        =>  $thread_vote->updated_at
                ];
            }
        }
        if(!empty($insert_sub)){
            $res = $this->db->table('thread_vote_subitems')->insert($insert_sub);
            if($res === false){
                $this->db->rollBack();
                $this->outPut(ResponseCode::INVALID_PARAMETER, '编辑新增投票帖失败');
            }
        }
        $this->db->commit();
        return $this->jsonReturn(['voteIds' => [$thread_vote->id]]);
    }

    public function select()
    {
        $voteIds = $this->getParams('voteIds');
        $votes = DzqCache::hMGetCollection(CacheKey::LIST_THREADS_V3_VOTES, $voteIds, function ($voteIds) {
            return ThreadVote::query()->whereIn('id', $voteIds)->whereNull('deleted_at')->get();
        });
        $res = [];
        if(!empty($votes->toArray())){
            $res = array_map(function ($item){
                $subitems = DzqCache::hGet(CacheKey::LIST_THREADS_V3_VOTE_SUBITEMS, $item['id'], function ($thread_vote_id) {
                    return ThreadVoteSubitem::query()->where('thread_vote_id', $thread_vote_id)->whereNull('deleted_at')->get();
                });
                return  [
                    'voteTitle' =>  $item['vote_title'],
                    'choiceType' => $item['choice_type'],
                    'voteUsers'  => $item['vote_users'],
                    'expired_at'  => $item['expired_at'],
                    'is_expired'  => $item['expired_at'] > Carbon::now(),
                    'subitems'  =>  $subitems->toArray()
                ];
            }, $votes->toArray());
        }
        return $this->jsonReturn($res);
    }

    public function verification(){
        $input = [
            'vote_title' => $this->getParams('voteTitle'),
            'choice_type' => $this->getParams('choiceType'),
            'expired_at'  => $this->getParams('expiredAt'),
            'subitems' => $this->getParams('subitems'),
        ];
        $rules = [
            'vote_title' => 'required|string|max:200',
            'choice_type' => 'required|int|in:1,2',
            'expired_at'  => 'required|date',
            'subitems' => 'required|array|min:2',
        ];
        $this->dzqValidate($input, $rules);
        foreach ($input['subitems'] as $val){
            if(mb_strlen($val['content']) > self::SUBITEMS_LENGTH)    $this->outPut(ResponseCode::INVALID_PARAMETER, '投票选项最多50个字');
        }
        return $input;
    }

    public function updateCheckVar(){
        $input = [
            'vote_id' => $this->getParams('voteId'),
            'vote_title' => $this->getParams('voteTitle'),
            'choice_type' => $this->getParams('choiceType'),
            'subitems' => $this->getParams('subitems'),
        ];
        $rules = [
            'vote_id' => 'required|integer|min:1',
            'vote_title' => 'required|string|max:200',
            'choice_type' => 'required|int|in:1,2',
            'subitems' => 'required|array|min:2',
        ];
        $this->dzqValidate($input, $rules);
        foreach ($input['subitems'] as $val){
            if(mb_strlen($val['content']) > self::SUBITEMS_LENGTH)    $this->outPut(ResponseCode::INVALID_PARAMETER, '投票选项最多50个字');
        }
        return $input;
    }
}
