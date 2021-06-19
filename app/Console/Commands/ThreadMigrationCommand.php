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

use App\Formatter\Formatter;
use App\Models\Attachment;
use App\Models\Order;
use App\Models\Post;
use App\Models\PostGoods;
use App\Models\Question;
use App\Models\Thread;
use App\Models\ThreadRedPacket;
use App\Models\ThreadReward;
use App\Models\ThreadTag;
use App\Models\ThreadTom;
use App\Models\ThreadVideo;
use App\Models\Topic;
use App\Models\User;
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

    const V3_TYPE = 99;

    const LIMIT = 500;


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
        app('log')->info('开始数据迁移start');
        foreach ($this->old_type as $type){
            try {
                switch ($type){
                    case Thread::TYPE_OF_TEXT:
                        $this->info('文字start');
                        app('log')->info('文字start');
                        self::migrateText();
                        $this->info('文字end');
                        app('log')->info('文字end');
                        break;
                    case Thread::TYPE_OF_LONG:
                        $this->info('长文start');
                        app('log')->info('长文start');
                        self::migrateLong();
                        $this->info('长文end');
                        app('log')->info('长文end');
                        break;
                    case Thread::TYPE_OF_VIDEO:
                        $this->info('视频start');
                        app('log')->info('视频start');
                        self::migrateVideo();
                        $this->info('视频end');
                        app('log')->info('视频end');
                        break;
                    case Thread::TYPE_OF_IMAGE:
                        $this->info('图片start');
                        app('log')->info('图片start');
                        self::migrateImage();
                        $this->info('图片end');
                        app('log')->info('图片end');
                        break;
                    case Thread::TYPE_OF_AUDIO:
                        $this->info('音频start');
                        app('log')->info('音频start');
                        self::migrateAudio();
                        $this->info('音频end');
                        app('log')->info('音频end');
                        break;
                    case Thread::TYPE_OF_QUESTION:
                        $this->info('问答start');
                        app('log')->info('问答start');
                        self::migrateQuestion();
                        $this->info('问答end');
                        app('log')->info('问答end');
                        break;
                    case Thread::TYPE_OF_GOODS:
                        $this->info('商品start');
                        app('log')->info('商品start');
                        self::migrateGoods();
                        $this->info('商品end');
                        app('log')->info('商品end');
                        break;
                }
            }catch (\Exception $e){
                continue;
            }
        }
        app('log')->info('数据迁移end');
        //v3数据迁移之后，下面的操作会比较刺激 -- 修改 posts 中的 content 字段数据
        $page = 1;
        $limit = 500;
        app('log')->info('修改posts中content数据start');
        $data = self::getOldData($limit);
        $i = 0;
        try {
            while (!empty($data)){
                app('log')->info('修改posts中content数据start，开始次数：'.$i);
                $i ++;
                foreach ($data as $key => $val){
                    $this->db->beginTransaction();
                    foreach ($val as $vi){
                        $content = $vi['content'];
                        if(empty($content))     continue;
                        $content = self::s9eRender($content);
                        $content = self::v3Content($content);
//                        if(1){
//                            $content = self::renderTopic($content);
//                            $content = self::renderCall($content);
//                        }

                        //先将posts全部改掉
                        $res = $this->db->table('posts')->where('id', $vi['post_id'])->update(['content' => $content]);
                        if($res === false){
                            $this->db->rollBack();
                            $this->info('修改 posts 的content出错');
                            app('log')->info('修改 posts 的content出错', [$vi]);
                            break;
                        }
                    }
                    $thread_id = $key;
                    //最后将 posts 对应的 thread 的 type 修改为 99
                    $res = $this->db->table('threads')->where('id', $thread_id)->update(['type' => self::V3_TYPE]);
                    if($res === false){
                        $this->db->rollBack();
                        $this->info('修改 threads 出错');
                        app('log')->info('修改 threads 出错', ['thread_id' => $thread_id]);
                        break;
                    }
                    $this->db->commit();
                }
                $page += 1;
                $data = self::getOldData($limit);
            }
            app('log')->info('data完成', [$data]);
        }catch (\Exception $e){
            $this->db->rollBack();
            $this->info($e->getMessage());
        }
        app('log')->info('帖子内容 posts 的 content 修改完成');
    }


    public function migrateText(){
        $start_page = 0;
        while (!empty($list = self::getThreadText($start_page)) && !empty($list->toArray())){
            foreach ($list as $val){
                //如果数据已经存在则跳过
                $isset_thread = ThreadTag::where(['thread_id' => $val->id, 'tag' => ThreadTag::TEXT])->first();
                $thread_red_packets = ThreadRedPacket::where(['thread_id' => $val->id, 'post_id' => $val->post_id])->first();
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
                //文字贴也可以发红包
                if($thread_red_packets && !empty($thread_red_packets->toArray())){
                    $res = self::insertThreadTag($val, ThreadTag::RED_PACKET);
                    if(!$res){
                        $this->db->rollBack();
                        $this->error('text insert: thread_tag red error. thread data is : '.json_encode($val->toArray()));
                        break;
                    }
                    //还需要插入对应的  thread_tom
//                $order = Order::where(['thread_id' => $val->id, 'type' => Order::ORDER_TYPE_TEXT])->first();
                    $value = [
                        'thread_id' => $val->id,
                        'post_id'   => $val->post_id,
                        'rule'  =>  $thread_red_packets->rule,
                        'condition' =>  $thread_red_packets->condition,
                        'likenum'   =>  $thread_red_packets->likenum,
                        'money'     =>  $thread_red_packets->money,
                        'remain_money'  =>  $thread_red_packets->remain_money,
                        'number'    =>  $thread_red_packets->number,
                        'remain_number' =>  $thread_red_packets->remain_number,
                        'status'    =>  $thread_red_packets->status,
                        'updated_at'    =>  $thread_red_packets->updated_at,
                        'created_at'    =>  $thread_red_packets->created_at,
                        'id'        =>  $thread_red_packets->id,
                        'content'   =>  '红包帖'
                    ];
                    $value = json_encode($value);
                    $res = self::insertThreadTom($val, ThreadTag::RED_PACKET, '$0', $value);
                    if(!$res){
                        $this->db->rollBack();
                        $this->error('long attachment insert: thread_tom red_packet error. thread data is : '.json_encode($val->toArray()));
                        break;
                    }
                }
                $this->db->commit();
            }
            $start_page ++;
        }
    }


    public function migrateLong(){
        $start_page = 0;
        while (!empty($list = self::getThreadLong($start_page)) && !empty($list->toArray())){
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
                if($attachments && !empty($attachments->toArray())){
                    $res = self::insertThreadTag($val, ThreadTag::DOC);
                    if(!$res){
                        $this->db->rollBack();
                        $this->error('long insert: thread_tag doc error. thread data is : '.json_encode($val->toArray()));
                        break;
                    }
                }

                //image
                if($attachments_image && !empty($attachments_image->toArray())){
                    $res = self::insertThreadTag($val, ThreadTag::IMAGE);
                    if(!$res){
                        $this->db->rollBack();
                        $this->error('long insert: thread_tag image error. thread data is : '.json_encode($val->toArray()));
                        break;
                    }
                }

                //red_packet
                if($thread_red_packets && !empty($thread_red_packets->toArray())){
                    $res = self::insertThreadTag($val, ThreadTag::RED_PACKET);
                    if(!$res){
                        $this->db->rollBack();
                        $this->error('long insert: thread_tag red_packet error. thread data is : '.json_encode($val->toArray()));
                        break;
                    }
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
//                $order = Order::where(['thread_id' => $val->id, 'type' => Order::ORDER_TYPE_LONG])->first();
                    $key = '$'.$count;
                    $value = [
                        'thread_id' => $val->id,
                        'post_id'   => $val->post_id,
                        'rule'  =>  $thread_red_packets->rule,
                        'condition' =>  $thread_red_packets->condition,
                        'likenum'   =>  $thread_red_packets->likenum,
                        'money'     =>  $thread_red_packets->money,
                        'remain_money'  =>  $thread_red_packets->remain_money,
                        'number'    =>  $thread_red_packets->number,
                        'remain_number' =>  $thread_red_packets->remain_number,
                        'status'    =>  $thread_red_packets->status,
                        'updated_at'    =>  $thread_red_packets->updated_at,
                        'created_at'    =>  $thread_red_packets->created_at,
                        'id'        =>  $thread_red_packets->id,
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
            $start_page ++;
        }


    }

    public function migrateVideo(){
        $start_page = 0;
        while (!empty($list = self::getThreadVideo($start_page)) && !empty($list->toArray())){
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
            $start_page ++;
        }
    }

    public function migrateImage(){
        $start_page = 0;
        while(!empty($list = self::getThreadImage($start_page)) && !empty($list->toArray())){
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
            $start_page ++;
        }
    }

    public function migrateAudio(){
        $start_page = 0;
        while (!empty($list = self::getThreadAudio($start_page)) && !empty($list->toArray())){
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
            $start_page ++;
        }
    }

    public function migrateQuestion(){
        $start_page = 0;
        while(!empty($list = self::getThreadQuestion($start_page)) && !empty($list->toArray())){
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

                $q_type = 0;
                $q_price = $remain_money = 0;
                $answer_id = 0;
                $q_expired_at = $q_created_at = $q_updated_at = '';
                if($question && !empty($question->toArray())){
                    $q_type = !empty($question->be_user_id) ? 1 : 0;
                    $answer_id = 0;
                    $q_price = $question->price;
                    $q_expired_at = $question->expired_at;
                    $q_created_at = $question->created_at;
                    $q_updated_at = $question->updated_at;
                }
//            $q_orderSn = "";
                if($thread_reward && !empty($thread_reward->toArray())){
                    $q_type = $thread_reward->type;
                    $q_price = $thread_reward->money;
                    $remain_money = $thread_reward->remain_money;
                    $q_expired_at = $thread_reward->expired_at;
                    $answer_id = $thread_reward->answer_id;
                    $q_created_at = $thread_reward->created_at;
                    $q_updated_at = $thread_reward->updated_at;
//                $q_orderSn = Order::query()->where('thread_id', $val->id)->value('order_sn');
                }
                $count = 0;
                //统一成悬赏贴格式插入 thread_tom
                $key = '$'.$count;
                $count++;
                $body = [
                    'thread_id'  =>  $val->id,
                    'post_id'   =>  $val->post_id,
                    'type' =>  $q_type,
                    'user_id' =>  $val->user_id,
                    'answer_id' => $answer_id,
                    'money' =>  $q_price,
                    'remain_money' => $remain_money,
                    'expired_at'    =>  $q_expired_at,
                    'updated_at'    =>  $q_updated_at,
                    'created_at'    =>  $q_created_at,
                    'id'            =>  !empty($thread_reward) ? $thread_reward->id : 0,        //这里放悬赏id
                    'content'       =>  null
                ];
                $value = json_encode($body);
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
            $start_page ++;
        }
    }

    public function migrateGoods(){
        $start_page = 0;
        while (!empty($list = self::getThreadGoods($start_page)) && !empty($list->toArray())){
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
                        'id'        =>  $post_goods->id,
                        'userId'    =>  $post_goods->user_id,
                        'postId'    =>  $post_goods->post_id,
                        'platformId'    =>  $post_goods->platform_id,
                        'title'     =>  $post_goods->title,
                        'price'     =>  $post_goods->price,
                        'imagePath' =>  self::preHttps($post_goods->image_path),
                        'type'      =>  $post_goods->type,
                        'status'    =>  $post_goods->status,
                        'readyContent'  =>  $post_goods->ready_content,
                        'detailCcontent'    =>  $post_goods->detail_content,
                        'createdAt'     =>  $post_goods->created_at,
                        'updatedAt'     =>  $post_goods->updated_at,
                    ];
                    $value = json_encode($body);
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
            $start_page ++;
        }
    }


    public function preHttps($url){
        if(strpos($url, 'http') === false){
            $url = 'https://'.$url;
        }
        return $url;
    }

    public function getThreadStatus($thread){
        if(!empty($thread->deleted_at)){
            return -1;
        }
        return 0;
    }


    public function insertThreadTag($thread, $tag){
        return $this->db->table('thread_tag')->insert([
            'thread_id' =>  $thread->id,
            'tag'   =>  $tag,
            'created_at'    =>  $thread->created_at,
            'updated_at'    =>  $thread->updated_at
        ]);

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

    //通过s9e，将threads中的 content 转为接口获取的 html 渲染格式
    public function s9eRender($text){
        return $this->app->make(Formatter::class)->render($text);
    }

    //将s9e render 渲染后的数据，正则匹配替换调表情，如不切换，当站长更换域名时，表情url会失效
    public function v3Content($text){
        preg_match_all('/<img.*?emoji\/qq.*?>/i', $text, $m1);
        if(empty($m1[0])){
            return $text;
        }
        $searches = $m1[0];
        $replaces = [];
        foreach ($searches as $key => $search) {
            preg_match('/:[a-z]+?:/i', $search, $m2);
            if(empty($m2[0])){      //没有匹配上
                unset($searches[$key]);
                continue;
            }
            $emoji = $m2[0];
            $replaces[] = $emoji;
        }
        $text = str_replace($searches, $replaces, $text);
        return $text;
    }

    public function renderTopic($text){
        preg_match_all('/#.+?#/', $text, $topic);
        if(empty($topic)){
            return  $text;
        }
        $topic = $topic[0];
        $topic = str_replace('#', '', $topic);
        $topics = Topic::query()->select('id', 'content')->whereIn('content', $topic)->get()->map(function ($item) {
            $item['content'] = '#' . $item['content'] . '#';
            $item['html'] = sprintf('<span id="topic" value="%s">%s</span>', $item['id'], $item['content']);
            return $item;
        })->toArray();
        foreach ($topics as $val){
            $text = preg_replace("/{$val['content']}/", $val['html'], $text, 1);
        }
        return $text;
    }

    public function renderCall($text){
        preg_match_all('/@.+? /', $text, $call);
        if(empty($call)){
            return  $text;
        }
        $call = $call[0];
        $call = str_replace(['@', ' '], '', $call);
        $ats = User::query()->select('id', 'username')->whereIn('username', $call)->get()->map(function ($item) {
            $item['username'] = '@' . $item['username'];
            $item['html'] = sprintf('<span id="member" value="%s">%s</span>', $item['id'], $item['username']);
            return $item;
        })->toArray();
        foreach ($ats as $val){
            $text = preg_replace("/{$val['username']}/", "{$val['html']}", $text, 1);
        }
        return $text;
    }

    //获取老数据 threads 、posts
    public function getOldData($limit){
        $data = [];
        $threadIds = Thread::query()->where('type','!=', self::V3_TYPE)
            ->limit($limit)->pluck('id')->toArray();
        if(empty($threadIds))   return $data;
        $posts = Post::query()->whereIn('thread_id', $threadIds)
            ->where('user_id', '!=', 0)
            ->whereNotNull('user_id')
            ->get(['id', 'content', 'thread_id']);
        foreach ($posts as $val){
            $data[$val->thread_id][] = [
                'post_id'   =>  $val->id,
                'content'   =>  $val->content
            ];
        }
        return $data;
    }


    //获取文字贴数据
    public function getThreadText($start_page){
        return  $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_TEXT)
            ->where('p.is_first', 1)
            ->offset($start_page * self::LIMIT)
            ->limit(self::LIMIT)
            ->get(['t.id','t.created_at','t.deleted_at','t.updated_at','p.id as post_id']);
    }

    //获取长文贴数据
    public function getThreadLong($start_page){
        return  $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_LONG)
            ->where('p.is_first', 1)
            ->offset($start_page * self::LIMIT)
            ->limit(self::LIMIT)
            ->get(['t.id','t.created_at','t.deleted_at','t.updated_at','p.id as post_id']);
    }

    //获取视频帖数据
    public function getThreadVideo($start_page){
        return  $this->db->table('threads as t')
            ->where('t.type', Thread::TYPE_OF_VIDEO)
            ->offset($start_page * self::LIMIT)
            ->limit(self::LIMIT)
            ->get(['t.id','t.created_at','t.deleted_at','t.updated_at','t.is_draft']);
    }

    //获取图片帖数据
    public function getThreadImage($start_page){
        return  $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_IMAGE)
            ->where('p.is_first', 1)
            ->offset($start_page * self::LIMIT)
            ->limit(self::LIMIT)
            ->get(['t.id','t.created_at','t.deleted_at','t.updated_at','p.id as post_id']);
    }

    //获取音频数据
    public function getThreadAudio($start_page){
        return  $this->db->table('threads as t')
            ->where('t.type', Thread::TYPE_OF_AUDIO)
            ->offset($start_page * self::LIMIT)
            ->limit(self::LIMIT)
            ->get(['t.id','t.created_at','t.deleted_at','t.updated_at']);
    }

    //获取问题数据
    public function getThreadQuestion($start_page){
        return  $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_QUESTION)
            ->where('p.is_first', 1)
            ->offset($start_page * self::LIMIT)
            ->limit(self::LIMIT)
            ->get(['t.id','t.created_at','t.deleted_at','t.updated_at','t.user_id','p.id as post_id']);
    }

    //获取商品数据
    public function getThreadGoods($start_page){
        return  $this->db->table('threads as t')
            ->join('posts as p','t.id','=','p.thread_id')
            ->where('t.type', Thread::TYPE_OF_GOODS)
            ->where('p.is_first', 1)
            ->offset($start_page * self::LIMIT)
            ->limit(self::LIMIT)
            ->get(['t.id','t.created_at','t.deleted_at','t.updated_at','p.id as post_id']);
    }

}
