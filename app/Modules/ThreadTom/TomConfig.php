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

namespace App\Modules\ThreadTom;

class TomConfig
{

    public static $map = [
        100 => [
            'enName' => 'TEXT',
            'desc' => '自定义文本模块',
            'service' => \App\Modules\ThreadTom\Busi\TextBusi::class
        ],
        101 => [
            'enName' => 'IMAGE',
            'desc' => '图片类',
            'service' => \App\Modules\ThreadTom\Busi\ImageBusi::class
        ],
        102 => [
            'enName' => 'VOICE',
            'desc' => '语音',
            'service' => \App\Modules\ThreadTom\Busi\VoiceBusi::class
        ],
        103 => [
            'enName' => 'VIDEO',
            'desc' => '视频',
            'service' => \App\Modules\ThreadTom\Busi\VideoBusi::class
        ],
        104 => [
            'enName' => 'GOODS',
            'desc' => '商品',
            'service' => \App\Modules\ThreadTom\Busi\GoodsBusi::class
        ],
        105 => [
            'enName' => 'QA',
            'desc' => '问答',
            'service' => \App\Modules\ThreadTom\Busi\QABusi::class
        ],
        106 => [
            'enName' => 'REDPACK',
            'desc' => '红包',
            'service' => \App\Modules\ThreadTom\Busi\RedPackBusi::class
        ],
        107 => [
            'enName' => 'REWARD',
            'desc' => '悬赏',
            'service' => \App\Modules\ThreadTom\Busi\RewardBusi::class
        ],
        108 => [
            'enName' => 'VOTE',
            'desc' => '投票',
            'service' => \App\Modules\ThreadTom\Busi\VoteBusi::class
        ],
        109 => [
            'enName' => 'QUEUE',
            'desc' => '排队接龙',
            'service' => \App\Modules\ThreadTom\Busi\QueueBusi::class
        ]
    ];
}
