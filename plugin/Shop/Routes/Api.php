<?php

/**@var Discuz\Http\RouteCollection $route*/

//拉取微信小商店商品列表
$route->get('wxshop/list', 'wxshop.list', \Plugin\Shop\Controller\WxShopListController::class);

//解析原商品
$route->post('goods/analysis', 'goods.analysis', \Plugin\Shop\Controller\ResourceAnalysisGoodsController::class);
