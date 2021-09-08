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

    const TOM_TEXT = 100;//文字内容，目前不单独作为扩展插件存储
    const TOM_IMAGE = 101;
    const TOM_AUDIO = 102;
    const TOM_VIDEO = 103;
    const TOM_GOODS = 104;
    const TOM_REDPACK = 106;
    const TOM_REWARD = 107;
    const TOM_DOC = 108;
    const TOM_VOTE = 109;

    public static $map = [
        self::TOM_TEXT => [
            'enName' => 'Text',
            'desc' => '文字',
            'service' => ''
        ],
        self::TOM_IMAGE => [
            'enName' => 'Image',
            'desc' => '图片',
            'service' => \App\Modules\ThreadTom\Busi\ImageBusi::class
        ],
        self::TOM_AUDIO => [
            'enName' => 'Audio',
            'desc' => '语音',
            'service' => \App\Modules\ThreadTom\Busi\AudioBusi::class
        ],
        self::TOM_VIDEO => [
            'enName' => 'Video',
            'desc' => '视频',
            'service' => \App\Modules\ThreadTom\Busi\VideoBusi::class
        ],
        self::TOM_GOODS => [
            'enName' => 'Goods',
            'desc' => '商品',
            'service' => \App\Modules\ThreadTom\Busi\GoodsBusi::class
        ],
        self::TOM_REDPACK => [
            'enName' => 'RedPacket',
            'desc' => '红包',
            'service' => \App\Modules\ThreadTom\Busi\RedPackBusi::class
        ],
        self::TOM_REWARD => [
            'enName' => 'Reward',
            'desc' => '悬赏',
            'service' => \App\Modules\ThreadTom\Busi\RewardBusi::class
        ],
        self::TOM_DOC => [
            'enName' => 'Attachment',
            'desc' => '文件附件',
            'service' => \App\Modules\ThreadTom\Busi\DocBusi::class
        ],
        self::TOM_VOTE => [
            'enName' => 'Vote',
            'desc' => '投票',
            'service' => \App\Modules\ThreadTom\Busi\VoteBusi::class
        ]
    ];
}
