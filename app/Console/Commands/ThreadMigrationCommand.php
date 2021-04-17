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

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\Post;
use App\Models\PostGoods;
use App\Models\Question;
use App\Models\Thread;
use App\Models\ThreadRedPacket;
use App\Models\ThreadReward;
use App\Models\ThreadText;
use App\Models\ThreadVideo;
use App\Repositories\ThreadVideoRepository;
use Carbon\Carbon;
use Discuz\Console\AbstractCommand;
use Discuz\Foundation\Application;
use Discuz\Qcloud\QcloudTrait;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;

class ThreadMigrationCommand extends AbstractCommand
{

    protected $signature = 'thread:migration';

    protected $description = '帖子内容数据库迁移';

    protected $app;

    protected $db;

    protected $old_type;

    protected $attachment_type;

    protected $video_type;


    /**
     * AvatarCleanCommand constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {

        parent::__construct();

        $this->app = $app;
        $this->db = app('db');
        $this->old_type = [
            Thread::TYPE_OF_TEXT,
            Thread::TYPE_OF_LONG,
            Thread::TYPE_OF_VIDEO,
            Thread::TYPE_OF_IMAGE,
            Thread::TYPE_OF_AUDIO,
            Thread::TYPE_OF_QUESTION,
            Thread::TYPE_OF_GOODS
        ];

        $this->attachment_type = [
            Attachment::TYPE_OF_FILE    =>  110,
            Attachment::TYPE_OF_IMAGE   =>  101,
            Attachment::TYPE_OF_ANSWER  =>  111
        ];

        $this->video_type = [
            ThreadVideo::TYPE_OF_VIDEO  =>  103,
            ThreadVideo::TYPE_OF_AUDIO  =>  102
        ];



    }

    public function handle()
    {

        $this->info('开始帖子内容数据迁移start');
        foreach ($this->old_type as $type){
            try {
                switch ($type){
                    case Thread::TYPE_OF_TEXT:
                        self::migrateText();
                        break;
                    case Thread::TYPE_OF_LONG:
                        self::migrateLong();
                        break;
                    case Thread::TYPE_OF_VIDEO:
                        self::migrateVideo();
                        break;
                    case Thread::TYPE_OF_IMAGE:
                        self::migrateImage();
                        break;
                    case Thread::TYPE_OF_AUDIO:
                        self::migrateAudio();
                        break;
                    case Thread::TYPE_OF_QUESTION:
                        self::migrateQuestion();
                        break;
                    case Thread::TYPE_OF_GOODS:
                        self::migrateGoods();
                        break;
                }
            }catch (\Exception $e){
                continue;
            }

        }
        $this->info('帖子内容数据迁移end');
    }


    public function migrateText(){
        $this->info('迁移文字帖start');
        $list = $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_TEXT)
            ->where('p.is_first', 1)
            ->get(['t.*','p.content']);
        foreach ($list as $val){
            //如果数据已经存在则跳过
            $isset_thread = ThreadText::find($val->id);
            if(!empty($isset_thread))       continue;
            $this->db->beginTransaction();
            $status = self::getThreadStatus($val);
            //先插 thread_text
            $res = self::insertThreadText($val, $status);
            if(!$res){
                $this->db->rollBack();
                $this->error('text insert: thread_text error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //再插 thread_hot
            $res = self::insertThreadHot($val);
            if(!$res){
                $this->db->rollBack();
                $this->error('text insert: thread_hot error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //文字帖不插 thread_tom 数据
            $this->db->commit();
        }
        $this->info('迁移文字帖end');
    }


    public function migrateLong(){
        $this->info('迁移长文帖start');
        //todo
        $list = $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_LONG)
            ->where('p.is_first', 1)
            ->get(['t.*','p.content','p.id as post_id']);
        foreach ($list as $val){
            //如果数据已经存在则跳过
            $isset_thread = ThreadText::find($val->id);
            if(!empty($isset_thread))       continue;
            $this->db->beginTransaction();
            //找出帖子中对应的 帖子附件 + 帖子图片 attachment
            $attachments = Attachment::query()->where('type_id',$val->post_id)->whereIn('type',[Attachment::TYPE_OF_FILE, Attachment::TYPE_OF_IMAGE])->orderBy('order')->get();
            $thread_red_packets = ThreadRedPacket::where(['thread_id' => $val->id, 'post_id' => $val->post_id])->first();
            $status = self::getThreadStatus($val);
            //先插 thread_text
            //针对长文帖可以做图文混排，所以要将原来的 content 中 IMG 替换成对应的 $0 $1 $2
            if(!empty($attachments) || !empty($thread_red_packets)){
                $val->content = self::replaceContent($val->content, $attachments);
                $content_last_count = -1;
                if(!empty($attachments)){
                    $content_last_count += $attachments->count();
                }
                if(!empty($thread_red_packets)){
                    $content_last_count += 1;
                    $val->content .= '{$'.$content_last_count.'}';
                }
            }
            $res = self::insertThreadText($val, $status);
            if(!$res){
                $this->db->rollBack();
                $this->error('long insert: thread_text error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //再插 thread_hot
            $res = self::insertThreadHot($val);
            if(!$res){
                $this->db->rollBack();
                $this->error('long insert: thread_hot error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            $count = 0;
            //最后插 thread_tom  插入附件、红包
            if(!empty($attachments)){
                //先计算这篇帖子有多少标识了 key 的，然后将没有标识 key 的，递增数字
                foreach ($attachments as $vi){
                    if(!empty($vi->key))     $count ++;
                }
                foreach ($attachments as $vo){
                    if(empty($vo->key)){
                        $vo->key = '$'.$count;
                        $count ++;
                    }
                }
                //最后开始插 thread_tom
                foreach ($attachments as $vo){
                    $res = self::insertThreadTom($vo, $val);
                    if(!$res){
                        $this->db->rollBack();
                        $this->error('long attachment insert: thread_tom error. thread data is : '.json_encode($val->toArray()));
                        break;
                    }
                }
            }
            if(!empty($thread_red_packets)){
                $res = $this->db->table('thread_tom')->insert([
                    'thread_id' =>  $val->id,
                    'tom_type'  =>  106,
                    'key'       =>  '$'.$count,
                    'value'     =>  json_encode($thread_red_packets->toArray()),
                    'created_at'    =>  $thread_red_packets->created_at,
                    'updated_at'    =>  $thread_red_packets->updated_at
                ]);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('long red_packets insert: thread_tom error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            $this->db->commit();
        }
        $this->info('迁移长文帖end');
    }

    public function migrateVideo(){
        $this->info('迁移视频帖start');
        $list = $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_VIDEO)
            ->where('p.is_first', 1)
            ->get(['t.*','p.content']);
        foreach ($list as $val){
            //如果数据已经存在则跳过
            $isset_thread = ThreadText::find($val->id);
            if(!empty($isset_thread))       continue;
            //如果是已发布，则选出已转码视频，否则取最后一个草稿视频内容
            if(empty($val->is_draft)){
                $thread_video = ThreadVideo::query()->where(['thread_id' => $val->id, 'type' => 0, 'status' => 1])->first();
            }else{
                $thread_video = ThreadVideo::query()->where(['thread_id' => $val->id, 'type' => 0, 'status' => 0])->orderBy('id','DESC')->first();
            }
            $this->db->beginTransaction();
            $status = self::getThreadStatus($val);
            //先插 thread_text
            //由于视频帖最后要放一个视频占位符，在 content 最后加个 $0
            if(!empty($thread_video)){
                $val->content = $val->content.'{$0}';
            }
            $res = self::insertThreadText($val, $status);
            if(!$res){
                $this->db->rollBack();
                $this->error('video insert: thread_text error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //再插 thread_hot
            $res = self::insertThreadHot($val);
            if(!$res){
                $this->db->rollBack();
                $this->error('video insert: thread_hot error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //插 thread_tom 数据
            if(!empty($thread_video)){
                $thread_video->key = '$0';
                $res = self::insertThreadTom($thread_video, $val);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('video insert: thread_tom error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            $this->db->commit();
        }
        $this->info('迁移视频帖end');
    }

    public function migrateImage(){
        $this->info('迁移图片帖start');
        $list = $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_IMAGE)
            ->where('p.is_first', 1)
            ->get(['t.*','p.content','p.id as post_id']);
        foreach ($list as $val){
            //如果数据已经存在则跳过
            $isset_thread = ThreadText::find($val->id);
            if(!empty($isset_thread))       continue;
            $this->db->beginTransaction();
            //找出帖子中对应的 帖子附件 + 帖子图片 attachment
            $attachments = Attachment::query()->where('type_id',$val->post_id)->where('type',Attachment::TYPE_OF_IMAGE)->orderBy('order')->get();
            $val->content .= '{$0}';
            $status = self::getThreadStatus($val);
            //先插 thread_text
            $res = self::insertThreadText($val, $status);
            if(!$res){
                $this->db->rollBack();
                $this->error('image insert: thread_text error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //再插 thread_hot
            $res = self::insertThreadHot($val);
            if(!$res){
                $this->db->rollBack();
                $this->error('image insert: thread_hot error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //最后判断插入 thread_tom
            if(!empty($attachments)){
                $res = $this->db->table('thread_tom')->insert([
                    'thread_id' =>  $val->id,
                    'tom_type'  =>  101,
                    'key'       =>  '$0',
                    'value'     =>  json_encode($attachments->toArray()),
                    'created_at'    =>  $val->created_at->timestamp,
                    'updated_at'    =>  $val->updated_at->timestamp
                ]);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('image insert: thread_tom error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            $this->db->commit();
        }
        $this->info('迁移图片帖end');
    }

    public function migrateAudio(){
        $this->info('迁移音频帖start');
        //todo
        $list = $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_AUDIO)
            ->where('p.is_first', 1)
            ->get(['t.*','p.content']);
        foreach ($list as $val){
            //如果数据已经存在则跳过
            $isset_thread = ThreadText::find($val->id);
            if(!empty($isset_thread))       continue;
            //如果是已发布，则选出已转码视频，否则取最后一个草稿视频内容
            if(empty($val->is_draft)){
                $thread_video = ThreadVideo::query()->where(['thread_id' => $val->id, 'type' => 1, 'status' => 1])->first();
            }else{
                $thread_video = ThreadVideo::query()->where(['thread_id' => $val->id, 'type' => 1, 'status' => 0])->orderBy('id','DESC')->first();
            }
            $this->db->beginTransaction();
            $status = self::getThreadStatus($val);
            //先插 thread_text
            //由于视频帖最后要放一个视频占位符，在 content 最后加个 $0
            if(!empty($thread_video)){
                $val->content = $val->content.'{$0}';
            }
            $res = self::insertThreadText($val, $status);
            if(!$res){
                $this->db->rollBack();
                $this->error('audio insert: thread_text error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //再插 thread_hot
            $res = self::insertThreadHot($val);
            if(!$res){
                $this->db->rollBack();
                $this->error('audio insert: thread_hot error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //插 thread_tom 数据
            if(!empty($thread_video)){
                $thread_video->key = '$0';
                $res = self::insertThreadTom($thread_video, $val);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('audio insert: thread_tom error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            $this->db->commit();
        }
        $this->info('迁移音频帖end');
    }

    public function migrateQuestion(){
        $this->info('迁移问答帖start');
        //todo
        $list = $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_QUESTION)
            ->where('p.is_first', 1)
            ->get(['t.*','p.content','p.id as post_id']);
        foreach ($list as $val){
            //如果数据已经存在则跳过
            $isset_thread = ThreadText::find($val->id);
            if(!empty($isset_thread))       continue;
            $this->db->beginTransaction();
            //找出帖子对应的 attachment图片 + question + thread_rewards
            $attachments = Attachment::query()->where('type_id',$val->post_id)->where('type', Attachment::TYPE_OF_IMAGE)->orderBy('order')->get();
            $question = Question::where('thread_id', $val->id)->first();
            $thread_reward = ThreadReward::where(['thread_id' => $val->id, 'post_id' => $val->post_id])->first();
            $status = self::getThreadStatus($val);
            //先插 thread_text
            //针对问答贴 中图片放在最后 + question + thread_reward
            $content_count = $attachments_key = $question_key = $thread_reward_key = 0;
            if(!empty($attachments)){
                $val->content .= '{$'.$content_count.'}';
                $attachments_key = $content_count;
                $content_count ++;
            }
            if(!empty($question)){
                $val->content .= '{$'.$content_count.'}';
                $question_key = $content_count;
                $content_count ++;
            }
            if(!empty($thread_reward)){
                $val->content .= '{$'.$content_count.'}';
                $thread_reward_key = $content_count;
            }
            //插入 thread_text
            $res = self::insertThreadText($val, $status);
            if(!$res){
                $this->db->rollBack();
                $this->error('question insert: thread_text error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //再插 thread_hot
            $res = self::insertThreadHot($val);
            if(!$res){
                $this->db->rollBack();
                $this->error('question insert: thread_hot error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //最后判断插入 thread_tom  attachment + question + thread_reward
            if(!empty($attachments)){
                $res = $this->db->table('thread_tom')->insert([
                    'thread_id' =>  $val->id,
                    'tom_type'  =>  111,
                    'key'       =>  '$'.$attachments_key,
                    'value'     =>  json_encode($attachments->toArray()),
                    'created_at'    =>  $val->created_at->timestamp,
                    'updated_at'    =>  $val->updated_at->timestamp
                ]);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('question attachment insert: thread_tom error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            if(!empty($question)){
                $res = $this->db->table('thread_tom')->insert([
                    'thread_id' =>  $val->id,
                    'tom_type'  =>  105,
                    'key'       =>  '$'.$question_key,
                    'value'     =>  json_encode($question->toArray()),
                    'created_at'    =>  $question->created_at->timestamp,
                    'updated_at'    =>  $question->updated_at->timestamp
                ]);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('question question insert: thread_tom error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            if(!empty($thread_reward)){
                $res = $this->db->table('thread_tom')->insert([
                    'thread_id' =>  $val->id,
                    'tom_type'  =>  107,
                    'key'       =>  '$'.$thread_reward,
                    'value'     =>  json_encode($question->toArray()),
                    'created_at'    =>  $thread_reward->created_at,
                    'updated_at'    =>  $thread_reward->updated_at
                ]);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('question thread_reward insert: thread_tom error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            $this->db->commit();
        }
        $this->info('迁移问答帖end');
    }

    public function migrateGoods(){
        $this->info('迁移商品帖start');
        //todo
        $list = $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_GOODS)
            ->where('p.is_first', 1)
            ->get(['t.*','p.content','p.id as post_id']);
        foreach ($list as $val){
            //如果数据已经存在则跳过
            $isset_thread = ThreadText::find($val->id);
            if(!empty($isset_thread))       continue;
            $this->db->beginTransaction();
            //找出帖子中对应的 帖子附件 + 帖子图片 attachment
            $attachments = Attachment::query()->where('type_id',$val->post_id)->where('type', Attachment::TYPE_OF_IMAGE)->orderBy('order')->get();
            $post_goods = PostGoods::where('post_id', $val->post_id)->first();
            $status = self::getThreadStatus($val);
            //先插 thread_text
            //针对长文帖可以做图文混排，所以要将原来的 content 中 IMG 替换成对应的 $0 $1 $2
            if(!empty($attachments) || !empty($post_goods)){
                $val->content = self::replaceContent($val->content, $attachments);
                $content_last_count = -1;
                if(!empty($attachments))    $content_last_count += $attachments->count();
                if(!empty($post_goods)){
                    $content_last_count += 1;
                    $val->content .= '{$'.$content_last_count.'}';
                }
            }
            $res = self::insertThreadText($val, $status);
            if(!$res){
                $this->db->rollBack();
                $this->error('goods insert: thread_text error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //再插 thread_hot
            $res = self::insertThreadHot($val);
            if(!$res){
                $this->db->rollBack();
                $this->error('goods insert: thread_hot error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //最后插 thread_tom   attachment -- 图文混排，图片附件
            $count = 0;
            if(!empty($attachments)){
                //先计算这篇帖子有多少标识了 key 的，然后将没有标识 key 的，递增数字
                foreach ($attachments as $vi){
                    if(!empty($vi->key))     $count ++;
                }
                foreach ($attachments as $vo){
                    if(empty($vo->key)){
                        $vo->key = '$'.$count;
                        $count ++;
                    }
                }
                //最后开始插 thread_tom
                foreach ($attachments as $vo){
                    $res = self::insertThreadTom($vo, $val);
                    if(!$res){
                        $this->db->rollBack();
                        $this->error('goods attachment insert: thread_tom error. thread data is : '.json_encode($val->toArray()));
                        break;
                    }
                }
            }
            if(!empty($post_goods)){
                $post_goods->key = '$'.$count;
                $post_goods->type = 104;
                $res = self::insertThreadTom($post_goods, $val);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('goods insert: thread_tom error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            $this->db->commit();
        }
        $this->info('迁移商品帖end');
    }


    public function getThreadStatus($thread){
        if(!empty($thread->deleted_at)){
            $status = -1;
        }elseif ($thread->is_display == 0){
            $status = 3;
        }elseif ($thread->is_approved == 0){
            $status = 0;
        }elseif ($thread->is_approved == 2){
            $status = 4;
        }elseif ($thread->is_draft){
            $status = 2;
        }else{
            $status = 1;
        }
        return $status;
    }

    public function insertThreadText($value, $status){
         return $this->db->table('thread_text')->insert(
            [
                'id'    =>  $value->id,
                'user_id'   =>  $value->user_id,
                'category_id'   =>  $value->category_id,
                'title'   =>  $value->title,
                'summary'   =>  Thread::find($value->id)->firstPost->summary,
                'text'   =>  $value->content,
                'longitude'   =>  $value->longitude,
                'latitude'   =>  $value->latitude,
                'address'   =>  $value->address,
                'location'   =>  $value->location,
                'is_sticky'   =>  $value->is_sticky,
                'is_essence'   =>  $value->is_essence,
                'is_anonymous'   =>  $value->is_anonymous,
                'is_site'   =>  $value->is_site,
                'status'   =>  $status,
                'created_at' =>  $value->created_at->timestamp,
                'updated_at' =>  $value->updated_at->timestamp,
            ]
        );
    }

    public function insertThreadHot($value){
        return $this->db->table('thread_hot')->insert(
            [
                'thread_id' =>  $value->id,
                'comment_count' =>  $value->post_count,
                'view_count' =>  $value->view_count,
                'reward_count' =>  $value->rewarded_count,
                'pay_count' =>  $value->paid_count,
                'last_post_time' =>  $value->posted_at->timestamp,
                'last_post_user' =>  $value->last_posted_user_id,
                'created_at' =>  $value->created_at->timestamp,
                'updated_at' =>  $value->updated_at->timestamp,
            ]
        );
    }

    public function replaceContent($content, $attachments){
        $i = 0;
        return preg_replace_callback(
            '(<IMG(.*)(title="(\d+)")(.*)\/IMG>)',
            function ($m) use ($attachments, &$i){
                foreach ($attachments as $vo){
                    if($m[3] == $vo->id){
                        $vo->key = '{$'.$i.'}';
                        $i ++;
                        return $vo->key;
                    }
                }
            },
            $content
        );
    }


    public function insertThreadTom($tom, $thread){
        if(in_array($thread->type,[Thread::TYPE_OF_VIDEO, Thread::TYPE_OF_AUDIO])){
            $tom_type = $this->video_type[$tom->type];
        }elseif(in_array($thread->type, [
            Thread::TYPE_OF_LONG,
            Thread::TYPE_OF_IMAGE,
            Thread::TYPE_OF_QUESTION
        ])){
            $tom_type = $this->attachment_type[$tom->type];
        }else{
            $tom_type = $tom->type;
        }
        if(empty($tom_type))    $tom_type = 0;

        return $this->db->table('thread_tom')->insert([
            'thread_id' =>  $thread->id,
            'tom_type'  =>  $tom_type,
            'key'       =>  $tom->key,
            'value'     =>  json_encode($tom->toArray()),
            'created_at'    =>  $tom->created_at,
            'updated_at'    =>  $tom->updated_at
        ]);

    }

}
