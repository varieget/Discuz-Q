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

namespace Plugin\Activity\Controller;


use App\Common\DzqConst;
use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Plugin\Activity\Model\ActivityUser;

class AppendController extends DzqController
{
    use ActivityTrait;
    private $activity = null;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return $this->checkPermission($userRepo);
    }

    public function main()
    {
        $activity = $this->activity;
        $totalNumber = $activity['total_number'];
        $activityUserBuilder = ActivityUser::query()->where([
            'activity_id'=>$activity->id,
            'status'=>DzqConst::BOOL_YES
        ]);
        if($totalNumber != 0){
            $activityUserBuilder->count() >= $totalNumber && $this->outPut(ResponseCode::INVALID_PARAMETER, '人数已满，报名失败');
        }
        $activityUser = $activityUserBuilder->where('user_id',$this->user->id)->first();
        if(!empty($activityUser)){
            $this->outPut(ResponseCode::RESOURCE_IN_USE,'您已经报名，不能重复报名');
        }
        $activityUser = new ActivityUser();
        $activityUser->thread_id = $activity->thread_id;
        $activityUser->activity_id = $activity->id;
        $activityUser->user_id = $this->user->id;
        $activityUser->status = DzqConst::BOOL_YES;
        if(!$activityUser->save()){
            $this->outPut(ResponseCode::DB_ERROR);
        }
        $this->outPut(0,'报名成功');
    }
}
