<?php

/**@var Discuz\Http\RouteCollection $route*/

//提交报名信息
$route->post('register/append', 'register.append', \Plugin\Activity\Controller\AppendController::class);
//取消报名
$route->post('register/cancel', 'register.cancel', \Plugin\Activity\Controller\CancelController::class);
//报名用户列表
$route->get('register/list', 'register.list', \Plugin\Activity\Controller\ListController::class);


