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
use App\Models\Order;
use App\Models\Post;
use App\Models\PostGoods;
use App\Models\Question;
use App\Models\Thread;
use App\Models\ThreadRedPacket;
use App\Models\ThreadReward;
use App\Models\ThreadTag;
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

/**
 * thread 迁移脚本，迁移数据库  thread_tag、thread_tom，其中帖子中图文混排中的图片情况先不管，只考虑单独添加的图片/附件
 */
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
            Attachment::TYPE_OF_FILE    =>  ThreadTag::DOC,
            Attachment::TYPE_OF_IMAGE   =>  ThreadTag::IMAGE,
            Attachment::TYPE_OF_ANSWER  =>  ThreadTag::REWARD       // 问答的类型迁移数据时全定义为 悬赏问答 的类型
        ];

        $this->video_type = [
            ThreadVideo::TYPE_OF_VIDEO  =>  ThreadTag::VIDEO,
            ThreadVideo::TYPE_OF_AUDIO  =>  ThreadTag::VOICE
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
            $isset_thread = ThreadTag::where(['thread_id' => $val->id, 'tag' => ThreadTag::TEXT])->first();
            if(!empty($isset_thread))       continue;
            $this->db->beginTransaction();
            $status = self::getThreadStatus($val);
            //先插 thread_tag  text 类型
            $res = self::insertThreadTag($val, ThreadTag::TEXT);
            if(!$res){
                $this->db->rollBack();
                $this->error('text insert: thread_tag text error. thread data is : '.json_encode($val->toArray()));
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
            $isset_thread = ThreadTag::where(['thread_id' => $val->id, 'tag' => ThreadTag::TEXT])->first();
            if(!empty($isset_thread))       continue;
            $this->db->beginTransaction();
            //找出帖子中对应的 帖子附件 + 帖子图片 attachment
            $attachments = Attachment::query()->where('type_id',$val->post_id)->whereIn('type',[Attachment::TYPE_OF_FILE])->orderBy('order')->get();
            $attachments_image = Attachment::query()->where('type_id',$val->post_id)->whereIn('type',[Attachment::TYPE_OF_IMAGE])->orderBy('order')->get();
            $thread_red_packets = ThreadRedPacket::where(['thread_id' => $val->id, 'post_id' => $val->post_id])->first();
            //先插 thread_tag  text
            $res = self::insertThreadTag($val, ThreadTag::TEXT);
            if(!$res){
                $this->db->rollBack();
                $this->error('long insert: thread_tag text error. thread data is : '.json_encode($val->toArray()));
                break;
            }

            //doc
            $res = self::insertThreadTag($val, ThreadTag::DOC);
            if(!$res){
                $this->db->rollBack();
                $this->error('long insert: thread_tag doc error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //red_packet
            $res = self::insertThreadTag($val, ThreadTag::RED_PACKET);
            if(!$res){
                $this->db->rollBack();
                $this->error('long insert: thread_tag red_packet error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            $count = 0;
            //最后插 thread_tom  插入附件、红包
            if($attachments && !empty($attachments->toArray())){
                $key = '$'.$count;
                $docIds = $attachments->pluck('id')->toArray();
                $count ++;
                $value = json_encode(['docIds' => $docIds]);
                $res = self::insertThreadTom($val, ThreadTag::DOC, $key, $value);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('long attachment insert: thread_tom doc error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            //最后插 thread_tom  插入图片
            if($attachments_image && !empty($attachments_image->toArray())){
                $key = '$'.$count;
                $docIds = $attachments_image->pluck('id')->toArray();
                $count ++;
                $value = json_encode(['imageIds' => $docIds]);
                $res = self::insertThreadTom($val, ThreadTag::IMAGE, $key, $value);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('long attachment insert: thread_tom images error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            if($thread_red_packets && !empty($thread_red_packets->toArray())){
                $order = Order::where(['thread_id' => $val->id])->first();
                $key = '$'.$count;
                $value = [
                    'condition' =>  $thread_red_packets->condition,
                    'likenum'   =>  $thread_red_packets->likenum,
                    'number'    =>  $thread_red_packets->number,
                    'rule'  =>  $thread_red_packets->rule,
                    'orderSn'   =>  $order->order_sn,
                    'price' =>  $thread_red_packets->money,
                    'content'   =>  '红包帖'
                ];
                $value = json_encode($value);
                $res = self::insertThreadTom($val, ThreadTag::RED_PACKET, $key, $value);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('long attachment insert: thread_tom red_packet error. thread data is : '.json_encode($val->toArray()));
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
            $isset_thread = ThreadTag::where(['thread_id' => $val->id, 'tag' => ThreadTag::VIDEO])->first();
            if(!empty($isset_thread))       continue;
            //如果是已发布，则选出已转码视频，否则取最后一个草稿视频内容
            if(empty($val->is_draft)){
                $thread_video = ThreadVideo::query()->where(['thread_id' => $val->id, 'type' => 0, 'status' => 1])->first();
            }else{
                $thread_video = ThreadVideo::query()->where(['thread_id' => $val->id, 'type' => 0, 'status' => 0])->orderBy('id','DESC')->first();
            }
            $this->db->beginTransaction();
            // 先插入 thread_tag
            $res = self::insertThreadTag($val, ThreadTag::VIDEO);
            if(!$res){
                $this->db->rollBack();
                $this->error('video insert: thread_tag text error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //插 thread_tom 数据
            if($thread_video && !empty($thread_video->toArray())){
                $key = '$0';
                $videoId = $thread_video->id;
                $value = json_encode(['videoId' => $videoId]);
                $res = self::insertThreadTom($val, ThreadTag::VIDEO, $key, $value);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('video attachment insert: thread_tom video error. thread data is : '.json_encode($val->toArray()));
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
            $isset_thread = ThreadTag::where(['thread_id' => $val->id, 'tag' => ThreadTag::IMAGE])->first();
            if(!empty($isset_thread))       continue;
            $this->db->beginTransaction();
            // 先插入 thread_tag
            $res = self::insertThreadTag($val, ThreadTag::IMAGE);
            if(!$res){
                $this->db->rollBack();
                $this->error('image insert: thread_tag  error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //找出帖子中对应的 帖子附件 + 帖子图片 attachment
            $attachments = Attachment::query()->where('type_id',$val->post_id)->where('type',Attachment::TYPE_OF_IMAGE)->orderBy('order')->get();
            //最后判断插入 thread_tom
            //插 thread_tom 数据
            if($attachments && !empty($attachments->toArray())){
                $key = '$0';
                $imageIds = $attachments->pluck('id')->toArray();
                $value = json_encode(['imageIds' => $imageIds]);
                $res = self::insertThreadTom($val, ThreadTag::IMAGE, $key, $value);
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
            $isset_thread = ThreadTag::where(['thread_id' => $val->id, 'tag' => ThreadTag::VOICE])->first();
            if(!empty($isset_thread))       continue;
            //如果是已发布，则选出已转码视频，否则取最后一个草稿视频内容
            if(empty($val->is_draft)){
                $thread_video = ThreadVideo::query()->where(['thread_id' => $val->id, 'type' => 1, 'status' => 1])->first();
            }else{
                $thread_video = ThreadVideo::query()->where(['thread_id' => $val->id, 'type' => 1, 'status' => 0])->orderBy('id','DESC')->first();
            }
            $this->db->beginTransaction();
            // 先插入 thread_tag
            $res = self::insertThreadTag($val, ThreadTag::VOICE);
            if(!$res){
                $this->db->rollBack();
                $this->error('audio insert: thread_tag  error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //插 thread_tom 数据
            if($thread_video && !empty($thread_video->toArray())){
                $key = '$0';
                $videoId = $thread_video->id;
                $value = json_encode(['audioId' => $videoId]);
                $res = self::insertThreadTom($val, ThreadTag::VOICE, $key, $value);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('audio attachment insert: thread_tom audio error. thread data is : '.json_encode($val->toArray()));
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
            $isset_thread = ThreadTag::where(['thread_id' => $val->id, 'tag' => ThreadTag::REWARD])->first();
            if(!empty($isset_thread))       continue;
            $this->db->beginTransaction();
            //找出帖子对应的 attachment图片 + question + thread_rewards
            $attachments = Attachment::query()->where('type_id',$val->post_id)->where('type', Attachment::TYPE_OF_IMAGE)->orderBy('order')->get();
            $question = Question::where('thread_id', $val->id)->first();
            $thread_reward = ThreadReward::where(['thread_id' => $val->id, 'post_id' => $val->post_id])->first();
            //先插入 thread_tag
            $res = self::insertThreadTag($val, ThreadTag::REWARD);
            if(!$res){
                $this->db->rollBack();
                $this->error('QA insert: thread_tag  error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            if($attachments && !empty($attachments->toArray())){
                $res = self::insertThreadTag($val, ThreadTag::IMAGE);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('QA insert: thread_tag attachment error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            $q_type = !empty($question->be_user_id) ? 1 : 0;
            $q_orderSn = "";
            $q_price = $question->price;
            $q_expired_at = $question->expired_at;
            if(!empty($thread_reward)){
                $q_type = $thread_reward->type;
                $q_price = $thread_reward->money;
                $q_expired_at = $thread_reward->expired_at;
                $q_orderSn = Order::query()->where('thread_id', $val->id)->value('order_sn');
            }
            $count = 0;
            //统一成悬赏贴格式插入 thread_tom
            $key = '$'.$count;
            $count++;
            $body = [
                'type'  =>  $q_type,
                'orderSn'   =>  $q_orderSn,
                'price' =>  $q_price,
                'expiredAt' =>  $q_expired_at
            ];
            $value = json_encode(['body' => $body]);
            $res = self::insertThreadTom($val, ThreadTag::REWARD, $key, $value);
            if(!$res){
                $this->db->rollBack();
                $this->error('question insert: thread_tom goods error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            if($attachments && !empty($attachments->toArray())){
                $key = '$'.$count;
                $imageIds = $attachments->pluck('id')->toArray();
                $value = json_encode(['imageIds' => $imageIds]);
                $res = self::insertThreadTom($val, ThreadTag::IMAGE, $key, $value);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('question insert: thread_tom attachment error. thread data is : '.json_encode($val->toArray()));
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
            $isset_thread = ThreadTag::where(['thread_id' => $val->id, 'tag' => ThreadTag::GOODS])->first();
            if(!empty($isset_thread))       continue;
            $this->db->beginTransaction();
            //找出帖子中对应的 帖子图片 attachment + 商品信息
            $attachments = Attachment::query()->where('type_id',$val->post_id)->where('type', Attachment::TYPE_OF_IMAGE)->orderBy('order')->get();
            $post_goods = PostGoods::where('post_id', $val->post_id)->first();
            // 先插入 thread_tag
            $res = self::insertThreadTag($val, ThreadTag::GOODS);
            if(!$res){
                $this->db->rollBack();
                $this->error('goods insert: thread_tag goods error. thread data is : '.json_encode($val->toArray()));
                break;
            }
            //判断是否有图片，如果有图片，还需要插 image 的tag
            if($attachments && !empty($attachments->toArray())){
                $res = self::insertThreadTag($val, ThreadTag::IMAGE);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('goods insert: thread_tag image error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }

            // 插入 thread_tom ，先插goods，再判断是否插入image类型
            $count = 0;
            if($post_goods && !empty($post_goods->toArray())){
                $key = '$'.$count;
                $count++;
                $body = [
                    'userId'    =>  $post_goods->user_id,
                    'platformId'    =>  $post_goods->platform_id,
                    'title'     =>  $post_goods->title,
                    'imagePath' =>  $post_goods->image_path,
                    'price'     =>  $post_goods->price,
                    'type'      =>  $post_goods->type,
                    'typeName'  =>  PostGoods::enumTypeName($post_goods->type),
                    'readyContent'  =>  $post_goods->ready_content,
                    'detailCcontent'    =>  $post_goods->detail_content
                ];
                $value = json_encode(['body' => $body]);
                $res = self::insertThreadTom($val, ThreadTag::GOODS, $key, $value);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('goods insert: thread_tom goods error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            if($attachments && !empty($attachments->toArray())){
                $key = '$'.$count;
                $imageIds = $attachments->pluck('id')->toArray();
                $value = json_encode(['imageIds' => $imageIds]);
                $res = self::insertThreadTom($val, ThreadTag::IMAGE, $key, $value);
                if(!$res){
                    $this->db->rollBack();
                    $this->error('goods insert: thread_tom attachment error. thread data is : '.json_encode($val->toArray()));
                    break;
                }
            }
            $this->db->commit();
        }
        $this->info('迁移商品帖end');
    }

/*
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
*/
    public function getThreadStatus($thread){
        if(!empty($thread->deleted_at)){
            return -1;
        }
        return 0;
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

    public function insertThreadTag($thread, $tag){
        return $this->db->table('thread_tag')->insert([
            'thread_id' =>  $thread->id,
            'tag'   =>  $tag,
            'created_at'    =>  $thread->created_at,
            'updated_at'    =>  $thread->updated_at
        ]);

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

    public function insertThreadTom($thread, $tom_type, $key, $value){
        return $this->db->table('thread_tom')->insert([
            'thread_id' =>  $thread->id,
            'tom_type'  =>  $tom_type,
            'key'   =>  $key,
            'value' =>  $value,
            'status'    => !empty($thread->deleted_at) ? -1 : 0,
            'created_at'    =>  $thread->created_at,
            'updated_at'    =>  $thread->updated_at
        ]);

    }


/*
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
*/

}
