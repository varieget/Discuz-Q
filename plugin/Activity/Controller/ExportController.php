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

namespace Plugin\Activity\Controller;

use App\Common\DzqConst;
use App\Common\Utils;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Plugin\Activity\Model\ActivityUser;
use Plugin\Activity\Model\ThreadActivity;

class ExportController extends DzqController
{
    use ActivityTrait;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $check = $this->checkPermission($userRepo, true);
        if (!$check) {
            return  $check;
        }
        //还需要本人或者超管才能有权限
        if ($this->activity->user_id == $this->user->id || $this->user->isAdmin()) {
            return true;
        } else {
            return false;
        }
    }

    public function main()
    {
        $activityId = $this->inPut('activityId');
        $activity_users = ActivityUser::query()->where(['activity_id' => $activityId, 'status' => DzqConst::BOOL_YES])->get();
        $export_list = [];
        foreach ($activity_users as $key=>$val) {
            $export_list[$key]['nickname'] = $val->user->nickname;
            $additional_info = json_decode($val->additional_info, 1);
            ksort($additional_info);

            if (!empty($additional_info)) {
                foreach ($additional_info as $ko => $vo) {
                    $export_list[$key][$ko] = $vo;
                }
            }
        }
        //处理execl表头
        $row = $export_list[0];
        $excel_title = ['昵称'];
        foreach ($row as $key => $val) {
            if ($key!= 'nickname') {
                $excel_title[] = ThreadActivity::$addition_map[ThreadActivity::$addition_info_map[$key]];
            }
        }
        $title = '活动报名用户'.time();
        Utils::localexport($excel_title, $title, $export_list);
        exit();
    }
}
