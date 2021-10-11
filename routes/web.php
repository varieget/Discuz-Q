<?php

use App\Install\Controller as InstallController;

$route->get('/plugin/{plugin_name}/{file_path}', 'plugin.file', \App\Http\Controller\PluginFileController::class);
$route->get('/install', 'install.index', InstallController\IndexController::class);
$route->post('/install', 'install', InstallController\InstallController::class);
$route->get('/upgrade', 'upgrade', InstallController\UpgradeLogController::class);
$route->get('/{other:.*}', 'other', \App\Http\Controller\IndexController::class);
