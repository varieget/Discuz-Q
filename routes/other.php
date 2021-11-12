<?php

use Discuz\Http\RouteCollection;
/**@var RouteCollection $route */
$route->get('/{other:.*}', 'other', \App\Http\Controller\IndexController::class);
