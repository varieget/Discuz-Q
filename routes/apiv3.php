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
//登录
$route->post('/users/username.login', 'username.login', ApiController\UsersV3\LoginController::class);
//注册
$route->post('/users/username.register', 'username.register', ApiController\UsersV3\RegisterController::class);
//控制用户名密码入口是否展示
$route->get('/users/username.login.isdisplay', 'username.login.isdisplay', ApiController\UsersV3\LsDisplayController::class);
//用户昵称检测
$route->post('/users/username.check', 'username.check', ApiController\UsersV3\CheckController::class);
//手机号（不区分端）
$route->post('/users/sms.send', 'sms.send', ApiController\UsersV3\SmsSendController::class);
$route->post('/users/sms.verify', 'sms.verify', ApiController\UsersV3\SmsVerifyController::class);
$route->post('/users/sms.login', 'sms.login', ApiController\UsersV3\SmsLoginController::class);
$route->post('/users/sms.bind', 'sms.bind', ApiController\UsersV3\SmsBindController::class);
$route->post('/users/sms.rebind', 'sms.rebind', ApiController\UsersV3\SmsRebindController::class);
$route->post('/users/sms.reset.pwd', 'sms.reset.pwd', ApiController\UsersV3\SmsResetPwdController::class);
$route->post('/users/sms.reset.pay.pwd', 'sms.reset.pay.pwd', ApiController\UsersV3\SmsResetPayPwdController::class);
//H5登录
$route->get('/users/wechat/h5.oauth', 'wechat.h5.oauth', ApiController\UsersV3\WechatH5OauthController::class);
$route->get('/users/wechat/h5.login', 'wechat.h5.login', ApiController\UsersV3\WechatH5LoginController::class);
$route->get('/users/wechat/h5.bind', 'wechat.h5.bind', ApiController\UsersV3\WechatH5BindController::class);
$route->get('/users/wechat/h5.rebind', 'wechat.h5.rebind', ApiController\UsersV3\WechatH5RebindController::class);
//小程序
$route->get('/users/wechat/miniprogram.login', 'wechat.miniprogram.login', ApiController\UsersV3\WechatMiniprogramLoginController::class);
$route->get('/users/wechat/miniprogram.bind', 'wechat.miniprogram.bind', ApiController\UsersV3\WechatMiniprogramBindController::class);
$route->get('/users/wechat/miniprogram.rebind', 'wechat.miniprogram.rebind', ApiController\UsersV3\WechatMiniprogramRebindController::class);
//手机浏览器（微信外）登录并绑定微信
//$route->get('/users/mobilebrowser/wechat/h5.bind', 'mobilebrowser.wechat.h5.bind', ApiController\UsersV3\MiniProgramSchemeGenController::class);
//$route->post('/users/mobilebrowser/username.login', 'mobilebrowser.username.login', ApiController\UsersV3\MobileBrowserLoginController::class);
//$route->get('/users/mobilebrowser/wechat/miniprogram.bind', 'mobilebrowser.wechat.miniprogram.bind', ApiController\UsersV3\MiniProgramSchemeGenController::class);
//过渡开关打开微信绑定自动创建账号
$route->get('/users/wechat/transition/username.autobind', 'wechat.transition.username.autobind', ApiController\UsersV3\WechatTransitionAutoRegisterController::class);

//登录页设置昵称
$route->post('/users/nickname.set', 'users.nickname.set', ApiController\UsersV3\NicknameSettingController::class);


//帖子查询
$route->get('/thread.detail','thread.detail',ApiController\ThreadsV3\ThreadDetailController::class);
$route->get('/thread.list','thread.list',ApiController\ThreadsV3\ThreadListController::class);
$route->get('/thread.stick','thread.stick',ApiController\ThreadsV3\ThreadStickController::class);
$route->get('/thread.likedusers','thread.likedusers',ApiController\ThreadsV3\ThreadLikedUsersController::class);
$route->get('/tom.detail','tom.detail',ApiController\ThreadsV3\SelectTomController::class);

//帖子变更
$route->post('/thread.create','thread.create',ApiController\ThreadsV3\CreateThreadController::class);
$route->post('/thread.delete','thread.delete',ApiController\ThreadsV3\DeleteThreadController::class);
$route->post('/thread.update','thread.update',ApiController\ThreadsV3\UpdateThreadController::class);
$route->post('/tom.delete','tom.delete',ApiController\ThreadsV3\DeleteTomController::class);
$route->post('/tom.update','tom.update',ApiController\ThreadsV3\UpdateTomController::class);
//首页配置接口
$route->get('/forum', 'forum.settings.v2', ApiController\Settings\ForumSettingsV2Controller::class);

$route->post('/thread.share','thread.share',ApiController\ThreadsV3\ThreadShareController::class);
$route->post('/goods/analysis', 'goods.analysis', ApiController\AnalysisV3\ResourceAnalysisGoodsController::class);
$route->get('/attachments', 'attachments.resource', ApiController\AttachmentV3\ResourceAttachmentController::class);
$route->post('/attachments', 'attachments.create', ApiController\AttachmentV3\CreateAttachmentController::class);
$route->get('/emoji', 'emoji.list', ApiController\EmojiV3\ListEmojiController::class);
$route->get('/follow', 'follow.list', ApiController\UsersV3\ListUserFollowController::class);
$route->post('/follow', 'follow.create', ApiController\UsersV3\CreateUserFollowController::class);
$route->post('/settings.create','settings.create',ApiController\SettingsV3\SetSettingsController::class);
$route->post('/permission.update', 'permission.update', ApiController\PermissionV3\UpdateGroupPermissionController::class);
$route->get('/groups.resource', 'groups.resource', ApiController\GroupV3\ResourceGroupsController::class);

$route->get('/topics.list', 'topics.list', ApiController\TopicV3\TopicListController::class);
$route->get('/users.list', 'users.list', ApiController\UsersV3\UsersListController::class);

$route->post('/order.create', 'order.create', ApiController\OrdersV3\CreateOrderController::class);
$route->post('/trade/notify/wechat', 'trade.notify.wechat', ApiController\TradeV3\Notify\WechatNotifyController::class);
$route->post('/trade/pay/order', 'trade.pay.order', ApiController\TradeV3\PayOrderController::class);
$route->get('/categories', 'categories', ApiController\CategoryV3\ListCategoriesController::class);
