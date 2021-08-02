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

use App\Api\Serializer\AttachmentSerializer;
use App\Common\CacheKey;
use App\Models\ThreadVote;
use App\Models\ThreadVoteSubitem;
use Carbon\Carbon;
use Discuz\Base\DzqCache;
use App\Common\ResponseCode;
use App\Models\Attachment;
use App\Models\Thread;
use App\Modules\ThreadTom\TomBaseBusi;
use Illuminate\Support\Facades\DB;

class VoteBusi extends TomBaseBusi
{
    const SUBITEMS_LENGTH = 50;

    public function create()
    {
        $input = $this->verification();
        DB::beginTransaction();
        // 先创建 thread_vote
        $thread_vote = ThreadVote::query()->create([
            'thread_id'    =>  $input['thread_id'],
            'vote_title'    =>  $input['vote_title'],
            'choice_type'    =>  $input['choice_type']
        ]);
        if($thread_vote === false){
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
        $res = DB::table('thread_vote_subitems')->insert($thread_vote_insert);
        if($res === false){
            $this->outPut(ResponseCode::INVALID_PARAMETER, '新增投票帖失败');
        }
        //这里的格式暂时作为数组的形式存，方便以后一个帖子多个投票的时候扩展
        return $this->jsonReturn(['voteIds' => [$thread_vote->id]]);
    }

    public function update()
    {
        $voteIds = $this->getParams('voteIds');
        if (count($voteIds) > 1) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '投票不能超过1个');
        }
        return $this->jsonReturn(['voteIds' => $voteIds]);
    }

    public function select()
    {
        $voteIds = $this->getParams('voteIds');
        $votes = DzqCache::hMGet(CacheKey::LIST_THREADS_V3_VOTES, $voteIds, function ($voteIds) {
            return ThreadVote::query()->whereIn('id', $voteIds)->whereNull('deleted_at')->get();
        });
        $res = [];
        if(!empty($votes)){
            $res = array_map(function ($item){
                $subitems = DzqCache::hGet(CacheKey::LIST_THREADS_V3_VOTE_SUBITEMS, $item['id'], function ($thread_vote_id) {
                    return ThreadVoteSubitem::query()->where('thread_vote_id', $thread_vote_id)->whereNull('deleted_at')->get();
                });
                return  [
                    'voteTitle' =>  $item['vote_title'],
                    'choice_type' => $item['choice_type'],
                    'vote_users'  => $item['vote_users'],
                    'expired_at'  => $item['expired_at'],
                    'is_expired'  => $item['expired_at'] > Carbon::now(),
                    'subitems'  =>  $subitems
                ];
            }, $votes);
        }

        return $this->jsonReturn($res);
    }

    public function verification(){
        $input = [
            'thread_id' => $this->getParams('threadId'),
            'vote_title' => $this->getParams('voteTitle'),
            'choice_type' => $this->getParams('choiceType'),
            'subitems' => $this->getParams('subitems'),
        ];
        $rules = [
            'thread_id' => 'required|integer|min:1',
            'vote_title' => 'required|string|max:200',
            'choice_type' => 'required|int|in:1,2',
            'subitems' => 'required|array|min:2',
        ];
        $this->dzqValidate($input, $rules);
        foreach ($input['subitems'] as $val){
            if(mb_strlen($val) > self::SUBITEMS_LENGTH)    $this->outPut(ResponseCode::INVALID_PARAMETER, '投票选项最多50个字');
        }
        return $input;
    }
}
