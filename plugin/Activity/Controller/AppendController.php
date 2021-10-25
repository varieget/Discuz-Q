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
use Plugin\Activity\Model\ThreadActivity;

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
        $additional_info = $this->inPut('additionalInfo') ?? [];
        if(empty($additional_info)){
            $additional_info = [
                'name'  =>  '',
                'mobile'    =>  '',
                'weixin'    =>  '',
                'address'   =>  ''
            ];
        }
        if(!empty($this->activity->additional_info_type)){
            $error_msg = '';
            switch ($this->activity->additional_info_type){
                case ThreadActivity::ADDITIONAL_INFO_TYPE_NAME && empty($additional_info['name']):
                    $error_msg = ' 姓名 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_MOBILE && empty($additional_info['mobile']):
                    $error_msg = ' 手机号 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_N_M && (empty($additional_info['name']) || empty($additional_info['mobile'])):
                    $error_msg = ' 姓名、手机号 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_WEIXIN && empty($additional_info['weixin']):
                    $error_msg = ' 微信号 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_N_W && (empty($additional_info['name']) || empty($additional_info['weixin'])):
                    $error_msg = ' 姓名、微信号 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_M_W && (empty($additional_info['mobile']) || empty($additional_info['weixin'])):
                    $error_msg = ' 手机号、微信号 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_N_M_W && (empty($additional_info['name']) || empty($additional_info['mobile']) || empty($additional_info['weixin'])):
                    $error_msg = ' 姓名、手机号、微信号 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_AD && empty($additional_info['address']):
                    $error_msg = ' 地址 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_AD_N && (empty($additional_info['name']) || empty($additional_info['address'])):
                    $error_msg = ' 姓名、地址 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_AD_M && (empty($additional_info['mobile']) || empty($additional_info['address'])):
                    $error_msg = ' 手机号、地址 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_AD_N_M && (empty($additional_info['name']) || empty($additional_info['mobile']) || empty($additional_info['address'])):
                    $error_msg = ' 姓名、手机号、地址 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_AD_W && (empty($additional_info['weixin']) || empty($additional_info['address'])):
                    $error_msg = ' 微信、地址 ';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_AD_N_W && (empty($additional_info['name']) || empty($additional_info['weixin']) || empty($additional_info['address'])):
                    $error_msg = ' 姓名、微信、地址';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_AD_M_W && (empty($additional_info['mobile']) || empty($additional_info['weixin']) || empty($additional_info['address'])):
                    $error_msg = ' 手机号、微信、地址';
                    break;
                case ThreadActivity::ADDITIONAL_INFO_TYPE_AD_N_M_W && (empty($additional_info['name']) || empty($additional_info['mobile']) || empty($additional_info['weixin']) || empty($additional_info['address'])):
                    $error_msg = ' 姓名、手机号、微信、地址 ';
                    break;
            }
            $this->outPut(ResponseCode::RESOURCE_IN_USE,'缺少必填信息：'.$error_msg);
        }

        $activityUser = new ActivityUser();
        $activityUser->thread_id = $activity->thread_id;
        $activityUser->activity_id = $activity->id;
        $activityUser->user_id = $this->user->id;
        $activityUser->status = DzqConst::BOOL_YES;
        $activityUser->additional_info = json_encode($additional_info);
        if(!$activityUser->save()){
            $this->outPut(ResponseCode::DB_ERROR);
        }
        $this->outPut(0,'报名成功');
    }
}
