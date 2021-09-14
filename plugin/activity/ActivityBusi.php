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

namespace Plugin\Activity;


use App\Modules\ThreadTom\TomBaseBusi;
use Plugin\Activity\Model\ThreadActivity;

class ActivityBusi extends TomBaseBusi
{
    public function checkPermission()
    {

    }

    public function select()
    {
        return $this->jsonReturn($this->body);
    }

    public function create()
    {
        $title = $this->getParams('title');
        $content = $this->getParams('content');
        $activityStartTime = $this->getParams('activityStartTime');
        $activityEndTime = $this->getParams('activityEndTime');
        $registerStartTime = $this->getParams('registerStartTime');
        $registerEndTime = $this->getParams('registerEndTime');
        $totalNumber = $this->getParams('totalNumber');
        $position = $this->getParams('position');

//        if (!empty($position)) {
//            $this->dzqValidate(
//                [
//                    'address' => $position['address'],
//                    'location' => $position['location'],
//                    'longitude' => $position['longitude'],
//                    'latitude' => $position['latitude']
//
//                ],
//                [
//                    'address' => 'required',
//                    'location' => 'required',
//                    'longitude' => 'required|numeric',
//                    'latitude' => 'required|numeric'
//                ],
//                [
//                    'address' => '缺少参数address',
//                    'location' => '缺少参数location',
//                    'longitude' => '经度数据错误',
//                    'latitude' => '纬度数据错误'
//                ]
//            );
//        }
        $this->dzqValidate(
            [
//                'now' => time(),
//                'userId'=>$this->user->id,
//                'threadId'=>$this->threadId,
                'title1' => $title,
                'content1' => $content,
//                'activityStartTime' => $activityStartTime,
//                'activityEndTime' => $activityEndTime,
//                'registerStartTime' => $registerStartTime,
//                'registerEndTime' => $registerEndTime,
//                'totalNumber' => $totalNumber,
            ],
            [
//                'now' => time(),
//                'userId'=>'required|nullable:false',
//                'threadId'=>'required|nullable:false',
                'title1' => 'required|max:50',
                'content1' => 'required|max:200',
//                'activityStartTime' => 'required|date|after_or_equal:now',
//                'activityEndTime' => 'required|date|after_or_equal:activityStartTime',
//                'registerStartTime' => 'required|date|after_or_equal:now',
//                'registerEndTime' => 'required|date|after_or_equal:registerStartTime',
//                'totalNumber' => 'required|integer|min:0',
            ],
            [
//                'userId'=>'用户未登录',
//                'threadId'=>'用户未发帖',
                'title1' => '标题12313不能超过50个字符',
                'content1' => '内容不能超过200个字符',
//                'activityStartTime' => '活动开始时间必须大于当前时间',
//                'activityEndTime' => '活动结束时间必须大于开始时间',
//                'registerStartTime' => '报名开始时间必须大于当前时间',
//                'registerEndTime' => '报名结束时间必须大于开始时间',
//                'totalNumber' => '报名人数必须大于0'
            ]
        );
        $activity = new ThreadActivity();
        $activity->setRawAttributes([
            'user_id'=>'用户未登录',
            'thread_id'=>'用户未发帖',
            'title' => '标题不能超过50个字符',
            'content' => '内容不能超过200个字符',
            'activity_start_time' => '活动开始时间必须大于当前时间',
            'activity_end_time' => '活动结束时间必须大于开始时间',
            'register_start_time' => '报名开始时间必须大于当前时间',
            'register_end_time' => '报名结束时间必须大于开始时间',
            'total_number' => '报名人数必须大于0'
        ]);
        if(!empty($position)){
            $activity->address = $position['address'];
            $activity->location = $position['location'];
            $activity->longitude = $position['longitude'];
            $activity->latitude = $position['latitude'];
        }
        if($activity->save()){
            return $this->jsonReturn($activity->toArray());
        }else{
            return false;
        }
    }

    public function update()
    {
        return $this->jsonReturn($this->body);
    }

    public function delete()
    {

    }


}
