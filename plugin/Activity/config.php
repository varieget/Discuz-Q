<?php
return [
    'name_cn' => '活动报名',
    'name_en' => 'activity',
    'description' => '帖子类型里添加报名插件',
    'type' => \App\Common\DzqConst::PLUGIN_THREAD,
    'app_id' => '612f4217ae890',
    'version' => 'v1.0.1',
    'status' => 1,
    'icon'=>'https://discuz.chat/dzq-img/active.png',
    'filter_enable' => false,
    'author' => [
        'name' => '腾讯科技（深圳）有限公司',
        'email' => 'coralchu@tencent.com'
    ],
    'routes' => [
        'register/append' => [
            'description' => '提交报名信息',
            'method' => 'POST',
            'controller' => \Plugin\Activity\Controller\AppendController::class
        ],
        'register/cancel' => [
            'description' => '取消报名',
            'method' => 'POST',
            'controller' => \Plugin\Activity\Controller\CancelController::class
        ],
        'register/list' => [
            'description' => '报名用户列表',
            'method' => 'GET',
            'controller' => \Plugin\Activity\Controller\ListController::class
        ]
    ],
    'busi' => \Plugin\Activity\ActivityBusi::class
];
