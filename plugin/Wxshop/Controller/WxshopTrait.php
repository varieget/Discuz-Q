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
use App\Models\Thread;
use Carbon\Carbon;
use Plugin\Activity\Model\ThreadActivity;
use Plugin\Wxshop\Model\AccessToken;

trait WxshopTrait
{

    public function getAccessToken($wxshopAppId){
        $data = AccessToken::query()->where("wx_app_id",$wxshopAppId)->first();
        if (!empty($data)){
            $dtTime = Carbon::parse($data->updated_at)->diffInSeconds(Carbon::now(),false);
            if ($dtTime < $data->expires_in){
                return $data->access_token;
            }
        }


        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid="."&secret=".;

        return;
    }

    public function checkPermission($userRepo, $guestEnable = false)
    {
        if (!$guestEnable) {
            if (empty($this->user) || $this->user->isGuest()) {
                $this->outPut(ResponseCode::UNAUTHORIZED);
            }
        }
        $thread = Thread::getOneActiveThread($this->activity->thread_id);
        return $userRepo->canViewThreadDetail($this->user, $thread);
    }
}
