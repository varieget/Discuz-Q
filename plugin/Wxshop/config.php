<?php
return [
    'name_cn' => '微信小商店',
    'name_en' => 'wxshop',
    'description' => '帖子类型里添加微信小商店插件',
    'type' => \App\Common\DzqConst::PLUGIN_THREAD,
    'app_id' => '61540fef8f4de8',
    'version' => 'v1.0.1',
    'status' => 1,
    'icon'=>'https://discuz.chat/dzq-img/active.png',
    'filter_enable' => false,
    'author' => [
        'name' => '腾讯科技（深圳）有限公司',
        'email' => 'simongguo@tencent.com'
    ],
    'routes' => [
        'shop/list' => [
            'description' => '拉取微信小商店列表',
            'method' => 'GET',
            'controller' => \Plugin\Wxshop\Controller\ListController::class
        ],
        'shop/add' => [
            'description' => '选择的商品',
            'method' => 'POST',
            'controller' => \Plugin\Wxshop\Controller\AddController::class
        ]
    ],
    'busi' => \Plugin\Wxshop\WxshopBusi::class
];

