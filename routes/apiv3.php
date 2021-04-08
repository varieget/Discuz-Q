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
use App\Api\Controller as ApiController;

/*
|--------------------------------------------------------------------------
| 注册/登录
|--------------------------------------------------------------------------
*/
//二维码生成
$route->get('/users/pc/wechat/h5.genqrcode', 'pc.wechat.h5.qrcode', ApiController\UsersV3\WechatH5QrCodeController::class);

//登录
$route->post('/users/username.login', 'username.login', ApiController\UsersV3\LoginController::class);

//注册
$route->post('/users/username.register', 'username.register', ApiController\UsersV3\RegisterController::class);

//控制用户名密码入口是否展示
$route->get('/users/username.login.isdisplay', 'username.login.isdisplay', ApiController\UsersV3\LsDisplayController::class);

//用户昵称检测
$route->get('/users/username.check', 'username.check', ApiController\UsersV3\CheckController::class);
