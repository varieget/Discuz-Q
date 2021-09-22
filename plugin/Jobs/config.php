<?php
return [
    'name_cn' => '招聘求职',
    'name_en' => 'jobs',
    'description' => '帖子类型里添加报名插件',
    'type' => \App\Common\DzqConst::PLUGIN_THREAD,
    'app_id' => '6130acd182770',
    'version' => 'v1.0.1',
    'status' => 1,
    'author' => [
        'name' => '腾讯科技（深圳）有限公司',
        'email' => 'coralchu@tencent.com'
    ],
    'routes' => [],
    'busi' => \Plugin\Jobs\JobBusi::class
];
