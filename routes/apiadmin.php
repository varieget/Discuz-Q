<?php

use App\Api\Controller as ApiController;

$route->get('/reports', 'reports.list', ApiController\ReportV3\ListReportsController::class);
$route->post('/reports/batch', 'reports.batchUpdate', ApiController\ReportV3\BatchUpdateReportsController::class);
$route->post('/reports/delete', 'reports.batchDelete', ApiController\ReportV3\BatchDeleteReportsController::class);
$route->get('/settings', 'settings.list', ApiController\SettingsV3\ListSettingsController::class);
$route->post('/settings/logo', 'settings.upload.logo', ApiController\SettingsV3\UploadLogoController::class);
$route->post('/settings/delete.logo', 'settings.delete.logo', ApiController\SettingsV3\DeleteLogoController::class);
$route->get('/siteinfo', 'site.info', ApiController\SiteInfoV3Controller::class);
$route->post('/settings.create','settings.create',ApiController\SettingsV3\SetSettingsController::class);

$route->post('/groups.create', 'groups.create', ApiController\GroupV3\CreateGroupController::class);
$route->get('/groups.list', 'groups.list', ApiController\GroupV3\ListGroupsController::class);
$route->post('/groups.batchupdate', 'groups.batchupdate', ApiController\GroupV3\BatchUpdateGroupController::class);
$route->post('/groups.batchdelete', 'groups.batchdelete', ApiController\GroupV3\BatchDeleteGroupsController::class);
$route->post('/users/update.user', 'users.admin', ApiController\UsersV3\UpdateAdminController::class);

// 财务
$route->get('/users.wallet.logs', 'users.wallet.logs', ApiController\WalletV3\UsersWalletLogsListController::class);
$route->get('/users.order.logs', 'users.order.logs', ApiController\OrderV3\UsersOrderLogsListController::class);
$route->get('/users.cash.logs', 'users.cash.logs', ApiController\WalletV3\UsersCashLogsListController::class);
$route->post('/wallet.cash.review', 'wallet.cash.review', ApiController\WalletV3\UserWalletCashReviewController::class);
$route->get('/statistic.finance', 'statistic.finance', ApiController\StatisticV3\FinanceProfileController::class);
$route->get('/statistic.financeChart', 'statistic.financeChart', ApiController\StatisticV3\FinanceChartController::class);

//内容分类
$route->get('/categories', 'categories', ApiController\CategoryV3\AdminListCategoriesController::class);
$route->post('/categories.create', 'categories.create', ApiController\CategoryV3\CreateCategoriesController::class);
$route->post('/categories.update', 'categories.update', ApiController\CategoryV3\BatchUpdateCategoriesController::class);
$route->post('/categories.delete', 'categories.delete', ApiController\CategoryV3\BatchDeleteCategoriesController::class);

$route->post('/permission.update', 'permission.update', ApiController\PermissionV3\UpdateGroupPermissionController::class);

$route->get('/groups.resource', 'groups.resource', ApiController\GroupV3\ResourceGroupsController::class);
//注册扩展
$route->get('/signinfields', 'signinfields.list', ApiController\SignInFieldsV3\ListAdminSignInController::class);
$route->post('/threads.batch', 'threads.batch', ApiController\ThreadsV3\BatchThreadsController::class);
//审核主题列表
$route->get('/check.thread.list', 'check.thread.list', ApiController\AdminV3\CheckThemeList::class);
//审核评论列表
$route->get('/check.posts.list', 'check.posts.list', ApiController\AdminV3\CheckReplyList::class);
$route->post('/check.sub', 'check.sub', ApiController\AdminV3\CheckSub::class);
//话题管理
$route->get('/topics.list', 'topics.list', ApiController\TopicV3\AdminTopicListController::class);
$route->post('/topics.batch.update', 'topics.batch.update', ApiController\TopicV3\BatchUpdateTopicController::class);
$route->post('/topics.batch.delete', 'topics.batch.delete', ApiController\TopicV3\BatchDeleteTopicController::class);

$route->get('/statistic/firstChart', 'statistic/firstChart', ApiController\statisticV3\FirstChartController::class);
