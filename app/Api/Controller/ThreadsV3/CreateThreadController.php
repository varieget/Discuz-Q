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


use App\Common\ResponseCode;
use App\Models\Category;
use App\Models\ThreadHot;
use App\Models\ThreadTom;
use App\Models\ThreadText;
use App\Modules\ThreadTom\TomTrait;
use Discuz\Base\DzqController;

class CreateThreadController extends DzqController
{
    use TomTrait;

    public function main()
    {
        //发帖权限
        $categoryId = $this->inPut('categoryId');
        $title = $this->inPut('title');
        $content = $this->inPut('content');
        $position = $this->inPut('position');
        $isAnonymous = $this->inPut('anonymous');//非必须
        $summary = $this->inPut('summary');//非必须
        if (!in_array($categoryId, Category::instance()->getValidCategoryIds($this->user))) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, $categoryId . '不合法');
        }
        if (!$this->canCreateThread($this->user, $categoryId)) {
            $this->outPut(ResponseCode::UNAUTHORIZED);
        }
        !empty($position) && $this->dzqValidate($position, [
            'longitude' => 'required',
            'latitude' => 'required',
            'address' => 'required',
            'location' => 'required'
        ]);
//        $data = [
//            'text' => '<p>去年11月以来，中国海关总署宣布，{$0}因从澳大利亚多地进口的原木{$1}中检出检疫性有害{$2}生物，根据相关法律暂停{$3}</p>',
//            '$0' => [
//                'tomId' => 101,//图片
//                'operation' => 'create',
//                'body' => [
//                    'imageIds' => [10, 11, 12],
//                    'desc' => '中国海关总署宣布'
//                ]
//            ],
//            '$1' => [
//                'tomId' => 103,//视频
//                'operation' => 'create',
//                'body' => [
//                    'videoIds' => [6, 7],
//                ]
//            ],
//            '$2' => [
//                'tomId' => 104,//商品
//                'operation' => 'create',
//                'body' => [
//                    'detail_content' => '小米10 双模5G 骁龙865 1亿像素8K电影相机 对称式立体声 12GB+256GB 钛银黑',
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
//                        ],
//                        [
//                            'title' => '上涨5%',
//                        ],
//                        [
//                            'title' => '下降5%',
//                        ],
//                        [
//                            'title' => '跌停',
//                        ]
//                    ],
//                    'question' => '你觉得明天700能涨多少？',
//                    'expiredTime' => '2021-04-02 20:00:00'
//                ]
//            ]
//        ];
        $params = [
            'categoryId' => $categoryId,
            'title' => $title,
            'content' => $content,
            'position' => $position,
            'isAnonymous' => $isAnonymous,
            'summary' => $summary
        ];
        list($text, $json) = $this->tomDispatcher($content);
        $this->createThread($text, $json, $params);
        $this->outPut(ResponseCode::SUCCESS);
    }


    /**
     * @desc 发布一个新帖子
     * @param $text
     * @param $json
     * @param $params
     */
    private function createThread($text, $json, $params)
    {
        $db = $this->getDB();
        $db->beginTransaction();
        try {
            $this->executeEloquent($text, $json, $params);
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            $this->info('createThread_error_' . $this->user->id, $e->getMessage());
//            $this->outPut(ResponseCode::DB_ERROR, 'log:createThread_error_' . $this->user->id);
            $this->outPut(ResponseCode::DB_ERROR, $e->getMessage());
        }
    }

    private function executeEloquent($text, $json, $params)
    {
        //插入text数据
        $tText = new ThreadText();
        list($ip, $port) = $this->getIpPort();
        $data = [
            'user_id' => $this->user->id,
            'category_id' => $params['categoryId'],
            'title' => $params['title'],
            'summary' => empty($params['summary']) ? $tText->getSummary($text) : $params['summary'],
            'text' => $text,
            'status' => ThreadText::STATUS_OK,
            'ip' => $ip,
            'port' => $port
        ];
        if (!empty($params['position'])) {
            $data['longitude'] = $params['position']['longitude'];
            $data['latitude'] = $params['position']['latitude'];
            $data['address'] = $params['position']['address'];
            $data['location'] = $params['position']['location'];
        }
        $tText->setRawAttributes($data);
        $tText->save();
        $threadId = $tText->id;
        //插入hot数据
        $tHot = new ThreadHot();
        $tHot->thread_id = $threadId;
        $tHot->save();
        //插入tom数据
        $attrs = [];
        foreach ($json as $key => $value) {
            $attrs[] = [
                'thread_id' => $threadId,
                'tom_type' => $value['tomId'],
                'key' => $key,
                'value' => json_encode($value['body'], 256)
            ];
        }
        ThreadTom::query()->insert($attrs);
    }

}
