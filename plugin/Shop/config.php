<?php
return [
    'name_cn' => '商店商品',
    'name_en' => 'shop',
    'description' => '帖子类型里添加商店商品的插件',
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
        'wxshop/list' => [
            'description' => '拉取微信小商店列表',
            'method' => 'GET',
            'controller' => \Plugin\Shop\Controller\WxShopListController::class
        ],
        'wxshop/info' => [
            'description' => '微信小商店信息',
            'method' => 'GET',
            'controller' => \Plugin\Shop\Controller\WxShopAddController::class
        ],
        'shop/uploadimage' => [
            'description' => '小商店上传图片',
            'method' => 'POST',
            'controller' => \Plugin\Shop\Controller\ShopUploadImageController::class
        ]
    ],
    'busi' => \Plugin\Shop\ShopBusi::class,
    'tom_ids'=>[   //额外的 tomid 列表，即该插件支持的tom包括 app_id和tom_ids
        '104'   //原生
    ],
];

