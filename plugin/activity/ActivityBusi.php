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


use App\Common\CacheKey;
use App\Common\DzqConst;
use App\Common\ResponseCode;
use App\Models\User;
use App\Modules\ThreadTom\TomBaseBusi;
use Discuz\Base\DzqCache;
use Plugin\Activity\Model\ActivityUser;
use Plugin\Activity\Model\ThreadActivity;

class ActivityBusi extends TomBaseBusi
{

    public function checkPermission()
    {

    }

    public function select()
    {
        $activityId = $this->getParams('activityId');
        $activity = ThreadActivity::query()->where([
            'id' => $activityId,
            'status' => DzqConst::BOOL_YES
        ])->first();
        if (empty($activity)) return false;
        $activityUser = ActivityUser::query()->where([
            'activity_id' => $activityId,
            'status' => DzqConst::BOOL_YES
        ]);
        $currentNumber = $activityUser->count();
        $userIds = $activityUser->orderByDesc('id')->limit(3)->select('user_id')->pluck('user_id')->toArray();
        $users = DzqCache::hMGet(CacheKey::LIST_THREADS_V3_USERS, $userIds, function ($userIds) {
            return User::instance()->getUsers($userIds);
        }, 'id');
        $registerUsers = [];
        foreach ($users as $user) {
            $registerUsers[] = [
                'userId' => $user['id'],
                'avatar' => $user['avatar'],
                'nickname' => $user['nickname']
            ];
        }
        $isRegistered = $activityUser->where('user_id', $this->user->id)->exists();
        $result = [
            'activityId' => $activity['id'],
            'title' => $activity['title'],
            'content' => $activity['content'],
            'activityStartTime' => $activity['activity_start_time'],
            'activityEndTime' => $activity['activity_end_time'],
            'registerStartTime' => $activity['register_start_time'],
            'registerEndTime' => $activity['register_end_time'],
            'totalNumber' => $activity['total_number'],
            'currentNumber' => $currentNumber,
            'position' => [
                'address' => $activity['address'],
                'location' => $activity['location'],
                'longitude' => $activity['longitude'],
                'latitude' => $activity['latitude']
            ],
            'isRegistered' => $isRegistered,
            'isExpired' => time() > strtotime($activity['register_start_time']),
            'isMemberFull' => $activity['total_number'] == 0 ? false : $activity['total_number'] < $currentNumber,
            'createdAt' => date('Y-m-d H:i:s', strtotime($activity['created_at'])),
            'registerUsers' => $registerUsers
        ];
        return $this->jsonReturn($result);
    }

    public function create()
    {
        $this->activityValidate();
        $activity = new ThreadActivity();
        $rawAttr = $this->getActivityRawAttr();
        $rawAttr += [
            'user_id' => $this->user->id,
            'thread_id' => $this->threadId
        ];
        $activity->setRawAttributes($rawAttr);
        if ($activity->save()) {
            return $this->jsonReturn(['activityId' => $activity['id']]);
        } else {
            return false;
        }
    }

    public function update()
    {
        $this->activityValidate();
        $activityId = $this->getParams('activityId');

        $activity = ThreadActivity::query()->where([
            'id' => $activityId,
            'status' => DzqConst::BOOL_YES
        ])->first();
        if (empty($activity)) $this->outPut(ResponseCode::INVALID_PARAMETER, '活动不存在');
        $rawAttr = $this->getActivityRawAttr();
        $activity->setRawAttributes($rawAttr);
        if ($activity->save()) {
            return true;
        } else {
            return false;
        }
    }

    public function delete()
    {
        $activityId = $this->getParams('activityId');
        $activity = ThreadActivity::query()->where([
            'id' => $activityId,
            'status' => DzqConst::BOOL_YES
        ])->first();
        if (empty($activity)) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, '活动不存在');
        }
        $activity->save();
        return true;
    }

    private function activityValidate()
    {
        $userId = $this->user->id;
        $threadId = $this->threadId;
        empty($userId) && $this->outPut(ResponseCode::INVALID_PARAMETER, '用户id无效');
        empty($threadId) && $this->outPut(ResponseCode::INVALID_PARAMETER, '帖子id无效');
        $position = $this->getParams('position');
        if (!empty($position)) {
            $this->dzqValidate($position, ['address' => 'required', 'location' => 'required', 'longitude' => 'required', 'latitude' => 'required']);
        }
        $now = date('Y-m-d H:i:s');
        $this->dzqValidate(
            $this->body,
            [
                'title' => 'required|max:50',
                'content' => 'required|max:200',
                'activityStartTime' => 'required|date|after:' . $now,
                'activityEndTime' => 'required|date|after:' . $this->getParams('activityStartTime'),
                'registerStartTime' => 'required|date|after:' . $now,
                'registerEndTime' => 'required|date|after:' . $this->getParams('registerStartTime'),
                'totalNumber' => 'required|integer|min:0',
            ]
        );
    }

    private function getActivityRawAttr()
    {
        $data = [
            'title' => $this->getParams('title'),
            'content' => $this->getParams('content'),
            'activity_start_time' => $this->getParams('activityStartTime'),
            'activity_end_time' => $this->getParams('activityEndTime'),
            'register_start_time' => $this->getParams('registerStartTime'),
            'register_end_time' => $this->getParams('registerEndTime'),
            'total_number' => $this->getParams('totalNumber')
        ];
        $position = $this->getParams('position');
        if (!empty($position)) {
            $data += [
                'address' => $position['address'],
                'location' => $position['location'],
                'longitude' => $position['longitude'],
                'latitude' => $position['latitude']
            ];
        }
        return $data;
    }
}
