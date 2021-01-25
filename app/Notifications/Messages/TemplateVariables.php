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

namespace App\Notifications\Messages;

trait TemplateVariables
{
    public $templateVariables = [
        1  => '系统新用户注册通知',        // 新用户注册通知
        2  => '系统注册审核通过通知',      // 注册审核通过通知
        3  => '系统注册审核不通过通知',    // 注册审核不通过通知
        4  => '系统内容审核不通过通知',    // 内容审核不通过通知
        5  => '系统内容审核通过通知',      // 内容审核通过通知
        6  => '系统内容删除通知',         // 内容删除通知
        7  => '系统内容精华通知',         // 内容精华通知
        8  => '系统内容置顶通知',         // 内容置顶通知
        9  => '系统内容修改通知',         // 内容修改通知
        10 => '系统用户禁用通知',         // 用户禁用通知
        11 => '系统用户解除禁用通知',      // 用户解除禁用通知
        12 => '系统用户角色调整通知',      // 用户角色调整通知
        13 => '微信新用户注册通知',        // 新用户注册通知
        14 => '微信用户状态通知',         // 注册审核通过通知
        15 => '微信用户状态通知',         // 注册审核不通过通知
        16 => '微信内容状态通知',         // 内容审核通过通知
        17 => '微信内容状态通知',         // 内容审核不通过通知
        18 => '微信内容状态通知',         // 内容删除通知
        19 => '微信内容状态通知',         // 内容精华通知
        20 => '微信内容状态通知',         // 内容置顶通知
        21 => '微信内容状态通知',         // 内容修改通知
        22 => '微信用户状态通知',         // 用户禁用通知
        23 => '微信用户状态通知',         // 用户解除禁用通知
        24 => '微信用户角色调整通知',      // 用户角色调整通知
        25 => '',                       // 内容回复通知
        26 => '',                       // 内容点赞通知
        27 => '',                       // 内容支付通知
        28 => '',                       // 内容@通知
        29 => '微信内容回复通知',         // 内容回复通知
        30 => '微信内容点赞通知',         // 内容点赞通知
        31 => '微信内容支付通知',         // 内容支付通知
        32 => '微信内容@通知',            // 内容@通知
        33 => '',                       // 提现通知
        34 => '',                       // 提现失败通知
        35 => '微信提现通知',             // 提现通知
        36 => '微信提现通知',             // 提现失败通知
        37 => '',                       // 分成收入通知
        38 => '微信分成收入通知',         // 分成收入通知
        39 => '',                       // 问答提问通知
        40 => '微信问答提问或过期通知',    // 问答提问通知
        41 => '',                       // 问答回答通知
        42 => '微信问答回答通知',         // 问答回答通知
        43 => '',                       // 过期通知
        44 => '微信问答提问或过期通知',    // 过期通知
    ];
}
