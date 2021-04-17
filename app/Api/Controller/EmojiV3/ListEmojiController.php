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

namespace App\Api\Controller\EmojiV3;

use App\Common\ResponseCode;
use App\Models\Emoji;
use Discuz\Base\DzqController;

class ListEmojiController extends DzqController
{

    public function main()
    {
        $Emoji = Emoji::all()->toArray();
        foreach ($Emoji as $k=>$v){
            $Emoji[$k] = $this->littleHump($v);
        }
        return $this->outPut(ResponseCode::SUCCESS,'',$Emoji);
    }

    protected function littleHump($data){
        $res = [];
        $keys = array_keys($data);
        foreach($keys as $val){
            if(strpos($val,'_')){
                $str = '';
                foreach (explode('_',$val) as $k=>$v){
                    if($k > 0){
                        $str .= ucfirst($v);
                    }else{
                        $str .= $v;
                    }
                }
                $res[$str] = $data[$val];
            }else{
                $res[$val] = $data[$val];
            }
        }
        return $res;
    }
}
