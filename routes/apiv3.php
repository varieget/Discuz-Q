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
$route->get('/users/pc/wechat/miniprogram.genqrcode', 'pc.wechat.miniprogram.genqrcode', ApiController\UsersV3\MiniProgramQrcodeController::class);
$route->get('/users/pc/wechat/h5.login', 'pc.wechat.h5.login.poll', ApiController\UsersV3\WechatPcLoginPollController::class);
$route->get('/users/pc/wechat/h5.bind', 'pc.wechat.h5.bind.poll', ApiController\UsersV3\WechatPcBindPollController::class);
$route->get('/users/pc/wechat/miniprogram.bind', 'pc.wechat.miniprogram.bind.poll', ApiController\UsersV3\MiniProgramPcBindPollController::class);
$route->get('/users/pc/wechat/miniprogram.login', 'pc.wechat.miniprogram.login.poll', ApiController\UsersV3\MiniProgramPcLoginPollController::class);
$route->get('/users/mobilebrowser/wechat/miniprogram.genscheme', 'pc.wechat.miniprogram.login.poll', ApiController\UsersV3\MiniProgramSchemeGenController::class);



