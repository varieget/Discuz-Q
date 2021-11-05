<?php

/**@var Discuz\Http\RouteCollection $route */

$route->withFrequency(function (\Discuz\Http\RouteCollection $route) {
    //提交报名信息
    $route->post('register/append', 'register.append', \Plugin\Activity\Controller\AppendController::class);
//取消报名
    $route->post('register/cancel', 'register.cancel', \Plugin\Activity\Controller\CancelController::class);
//报名用户列表
    $route->get('register/list', 'register.list', \Plugin\Activity\Controller\ListController::class);
//报名用户信息导出
    $route->get('register/export', 'register.export', \Plugin\Activity\Controller\ExportController::class);
//测试接口替换
    $route->get('register/thread.list', 'register.thread.list', \Plugin\Activity\Controller\ThreadListV1Controller::class, \App\Api\Controller\Threads\ThreadListController::class);
}, 30, 60);


