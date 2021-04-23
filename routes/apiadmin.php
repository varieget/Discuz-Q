<?php

use App\Api\Controller as ApiController;

$route->get('/reports', 'reports.list', ApiController\ReportV3\ListReportsController::class);
$route->post('/reports/batch', 'reports.batchUpdate', ApiController\ReportV3\BatchUpdateReportsController::class);
$route->post('/reports/delete', 'reports.batchDelete', ApiController\ReportV3\BatchDeleteReportsController::class);
$route->get('/settings', 'settings.list', ApiController\SettingsV3\ListSettingsController::class);
$route->post('/settings/logo', 'settings.upload.logo', ApiController\SettingsV3\UploadLogoController::class);
$route->post('/settings/delete.logo', 'settings.delete.logo', ApiController\SettingsV3\DeleteLogoController::class);
$route->get('/siteinfo', 'site.info', ApiController\SiteInfoV3Controller::class);

$route->post('/groups.create', 'groups.create', ApiController\GroupV3\CreateGroupController::class);
$route->get('/groups.list', 'groups.list', ApiController\GroupV3\ListGroupsController::class);
$route->post('/groups.batchupdate', 'groups.batchupdate', ApiController\GroupV3\BatchUpdateGroupController::class);
$route->post('/groups.batchdelete', 'groups.batchdelete', ApiController\GroupV3\BatchDeleteGroupsController::class);
