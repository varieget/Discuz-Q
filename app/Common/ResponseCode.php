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

namespace App\Common;


use function Complex\sec;

class ResponseCode
{
    const SUCCESS = 0;

    const JUMP_TO_LOGIN = -3001;
    const JUMP_TO_REGISTER = -3002;
    const JUMP_TO_AUDIT = -3003;
    const JUMP_TO_HOME_INDEX = -3004;

    const INVALID_PARAMETER = -4001;
    const UNAUTHORIZED = -4002;
    const RESOURCE_EXIST = -4003;
    const RESOURCE_NOT_FOUND = -4004;
    const RESOURCE_IN_USE = -4005;
    const CONTENT_BANNED = -4006;
    const VALIDATE_REJECT = -4007;
    const VALIDATE_IGNORE = -4008;

    const NET_ERROR = -5001;
    const INTERNAL_ERROR = -5002;
    const DB_ERROR = -5003;
    const EXTERNAL_API_ERROR = -5004;

    const UNKNOWN_ERROR = -6001;
    const DEBUG_ERROR = -6002;

    const PC_QRCODE_TIME_OUT = -7001;
    const PC_QRCODE_SCANNING_CODE = -7002;
    const PC_QRCODE_ERROR = -7003;
    const SESSION_TOKEN_EXPIRED = -7004;
    const NOT_FOUND_USER = -7005;
    const NOT_FOUND_USER_WECHAT = -7006;
    const PC_QRCODE_TIME_FAIL = -7007;
    const GEN_QRCODE_TYPE_ERROR = -7008;
    const MINI_PROGRAM_GET_ACCESS_TOKEN_ERROR = -7009;
    const MINI_PROGRAM_QR_CODE_ERROR = -7010;
    const PC_BIND_ERROR = -7011;
    const MINI_PROGRAM_SCHEME_ERROR = -7012;
    const DECRYPT_CODE_FAILURE = -7013;
    const MOBILE_IS_ALREADY_BIND = -7031;
    const REGISTER_CLOSE = -7032;
    const REGISTER_TYPE_ERROR = -7033;
    const USER_UPDATE_ERROR = -7034;
    const ACCOUNT_HAS_BEEN_BOUND = -7034;
    const ACCOUNT_WECHAT_IS_NULL = -7035;
    const BIND_ERROR = -7036;

    public static $codeMap = [
        self::JUMP_TO_LOGIN => '跳转到登录页',
        self::JUMP_TO_AUDIT=>'跳转到审核页',
        self::JUMP_TO_HOME_INDEX=>'跳转到首页',
        self::JUMP_TO_REGISTER =>'跳转到注册页',
        self::SUCCESS => '接口调用成功',
        self::INVALID_PARAMETER => '参数错误',
        self::UNAUTHORIZED => '没有权限',
        self::RESOURCE_EXIST => '资源已存在',
        self::RESOURCE_NOT_FOUND => '资源不存在',
        self::RESOURCE_IN_USE => '资源被占用',
        self::CONTENT_BANNED => '内容被禁用',
        self::VALIDATE_REJECT => '拒绝验证',
        self::VALIDATE_IGNORE => '忽略验证',
        self::NET_ERROR => '网络错误',
        self::INTERNAL_ERROR => '内部系统错误',
        self::EXTERNAL_API_ERROR => '外部接口错误',
        self::DB_ERROR => '数据库错误',
        self::UNKNOWN_ERROR => '未知错误',
        self::DEBUG_ERROR => '调试错误',
        self::PC_QRCODE_TIME_OUT => '二维码已失效，扫码超时',
        self::PC_QRCODE_SCANNING_CODE => '扫码中',
        self::PC_QRCODE_ERROR => '扫码失败，请重新扫码',
        self::SESSION_TOKEN_EXPIRED => 'SESSION TOKEN过期',
        self::NOT_FOUND_USER => '未找到用户',
        self::NOT_FOUND_USER_WECHAT => '未找到微信用户',
        self::PC_QRCODE_TIME_FAIL => '扫码登录失败',
        self::GEN_QRCODE_TYPE_ERROR => '生成二维码参数类型错误',
        self::MINI_PROGRAM_GET_ACCESS_TOKEN_ERROR => '全局token获取失败',
        self::MINI_PROGRAM_QR_CODE_ERROR => '小程序二维码生成失败',
        self::PC_BIND_ERROR => '绑定失败',
        self::MINI_PROGRAM_SCHEME_ERROR => '生成scheme失败',
        self::DECRYPT_CODE_FAILURE => '解密邀请码失败',
        self::REGISTER_CLOSE => '站点关闭注册',
        self::MOBILE_IS_ALREADY_BIND => '手机号已被绑定',
        self::REGISTER_TYPE_ERROR => '注册类型错误',
        self::USER_UPDATE_ERROR => '不可以使用相同的密码',
        self::ACCOUNT_HAS_BEEN_BOUND => '账户已经被绑定',
        self::ACCOUNT_WECHAT_IS_NULL => '账户微信为空',
        self::BIND_ERROR => '绑定错误',
    ];
}
