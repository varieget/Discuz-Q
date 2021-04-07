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

namespace App\Api\Controller\ThreadsV3;


use App\Models\ThreadHot;
use App\Models\ThreadObject;
use App\Models\ThreadText;
use App\Modules\ThreadTom\TomTrait;
use Discuz\Base\DzqController;

class CreateThreadController extends DzqController
{
    use TomTrait;

    public function main()
    {
        $data = $this->inPut('data');
        $data = [
            'text' => '<p>去年11月以来，中国海关总署宣布，{$0}因从澳大利亚多地进口的原木{$1}中检出检疫性有害{$2}生物，根据相关法律暂停{$3}</p>',
            '$0' => [
                'tomId' => 101,//图片
                'operation' => 'create',
                'body' => [
                    'imageIds' => [10, 11, 12],
                    'desc' => '中国海关总署宣布'
                ]
            ],
            '$1' => [
                'tomId' => 103,//视频
                'operation' => 'create',
                'body' => [
                    'videoIds' => [6, 7],
                ]
            ],
//            '$2' => [
//                'tomId' => 104,//商品
//                'operation' => 'create',
//                'body' => [
//                    'goodsName' => '小米10 双模5G 骁龙865 1亿像素8K电影相机 对称式立体声 12GB+256GB 钛银黑',
//                    'goodsUrl' => 'https://item.jd.com/100010534221.html',
//                    'imageUrl' => '',
//                    'price' => 250,
//                ]
//            ],
//            '$3' => [
//                'tomId' => 108,//投票
//                'operation' => 'create',
//                'body' => [
//                    'options' => [
//                        [
//                            'title' => '涨停',
////                            'number'=>100,
////                            'percent'=>0.20,
//                        ],
//                        [
//                            'title' => '上涨5%',
////                            'number'=>100,
////                            'percent'=>0.20,
//                        ],
//                        [
//                            'title' => '下降5%',
////                            'number'=>300,
////                            'percent'=>0.60,
//                        ],
//                        [
//                            'title' => '跌停',
////                            'number'=>300,
////                            'percent'=>0.60,
//                        ]
//                    ],
//                    'question' => '你觉得明天700能涨多少？',
//                    'expiredTime' => '2021-04-02 20:00:00'
//                ]
//            ]
        ];

        list($text, $json) = $this->tomDispatcher($data);
        $this->createThread($text, $json);
        $this->outPut(0, '', [$text, $json]);
    }

    /**
     *发布一个新帖子
     * todo 待完善
     * @param $text
     * @param $json
     */
    private function createThread($text, $json)
    {
        return true;
        $tText = new ThreadText();
        $tText->setRawAttributes([
            'user_id' => $this->user->id,
            'category_id' => $this->inPut('categoryId'),
            'title' => $this->inPut('title'),
            'summary' => $text->getSummary($text),
            'text' => $text,
            'longitude' => $this->inPut('longitude'),
            'latitude' => $this->inPut('latitude'),
            'address' => $this->inPut('address'),
            'location' => $this->inPut('location'),
            'status' => 1
        ]);
        $tText->save();

        $tHot = new ThreadHot();
        $tHot->thread_id = $tText->id;
        $tHot->save();

        //todo 批量插入
        foreach ($json as $k => $v) {
            $tObject = new ThreadObject();
            $tObject->setRawAttributes([
                'thread_id' => $tText->id,
                'type' => 100,
                'key' => $k,
                'value' => json_encode($v, 256),
            ]);
            $tObject->save();
        }
    }

}
