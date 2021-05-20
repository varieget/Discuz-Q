<?php

/**
 * Copyright (C) 2020 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Common;

class CacheKey
{
    //记录首页各个分类的数据缓存
    public const LIST_THREAD_HOME_INDEX = 'list_thread_home_index_';

    //记录各个缓存的key值，便于数据更新的时候删除
    public const LIST_THREAD_KEYS = 'list_thread_keys';

    //记录
    public const THREAD_RESOURCE_BY_ID = 'thread_resource_by_id_';

    public const POST_RESOURCE_BY_ID = 'post_resource_by_id_';

    //记录用户是否新注册用户
    public const NEW_USER_LOGIN = 'new_user_login_';

    //符合智能排序条件的id数组
    public const LIST_SEQUENCE_THREAD_INDEX = 'list_sequences_thread_index';

    public const LIST_SEQUENCE_THREAD_INDEX_KEYS = 'list_sequences_thread_index_keys';

    public const API_FREQUENCE = 'api_frequence';

    public const LIST_CATEGORIES = 'list_categories';

    public const LIST_V2_THREADS = 'list_v2_threads';

    // 存储小程序通知模板数据
    public const NOTICE_MINI_PROGRAM_TEMPLATES = 'notice_mini_program_templates';
    public const AUTH_USER_PREFIX = 'auth_user_';

    public const CHECK_PAID_GROUP = 'check_paid_group_';

    public const SETTINGS = 'settings';

    public const CATEGORIES = 'categories';

    public const LIST_EMOJI = 'list_emoji';

    public const LIST_GROUPS = 'list_groups';

    public const GROUP_PERMISSIONS = 'group_permissions';

    public const SEQUENCE = 'sequence';


    public const LIST_THREADS_V3_CREATE_TIME = 'list_threads_v3_create_time';
    public const LIST_THREADS_V3_SEQUENCE = 'list_threads_v3_sequence';
    //热点数据变更排序规则
    public const LIST_THREADS_V3_VIEW_COUNT = 'list_threads_v3_view_count';
    public const LIST_THREADS_V3_POST_TIME = 'list_threads_v3_post_time';
    public const LIST_THREADS_V3_POST_COUNT = 'list_threads_v3_post_count';
    public const LIST_THREADS_V3_PAID_COUNT = 'list_threads_v3_paid_count';
    public const LIST_THREADS_V3_REWARD_COUNT = 'list_threads_v3_rewarded_count';





    public const LIST_THREADS_V3_USERS = 'list_threads_v3_users';//发帖用户存储 id
    public const LIST_THREADS_V3_THREADS = 'list_threads_v3_threads';//帖子数据存储 id
    public const LIST_THREADS_V3_POSTS = 'list_threads_v3_posts';//帖子正文数据存储 thread_id
    public const LIST_THREADS_V3_ATTACHMENT = 'list_threads_v3_attachment';//帖子附件数据存储 id
    public const LIST_THREADS_V3_VIDEO = 'list_threads_v3_video';//帖子视频文件存储 id
    public const LIST_THREADS_V3_TAGS = 'list_threads_v3_tags';//帖子标签存储 thread_id
    public const LIST_THREADS_V3_TOMS = 'list_threads_v3_toms';//帖子插件存储 thread_id

    public const LIST_THREADS_V3_USER_PAY_ORDERS = 'list_threads_v3_user_pay_orders';//用户付费贴订单信息 user_id

    public const LIST_THREADS_V3_USER_REWARD_ORDERS = 'list_threads_v3_user_reward_orders';//打赏的订单信息 user_id

    public const LIST_THREADS_V3_GROUP_USER = 'list_threads_v3_group_user';//用户组 user_id

    public const LIST_THREADS_V3_SEARCH_REPLACE = 'list_threads_v3_search_replace';//替换标签、话题和艾特
    public const LIST_THREADS_V3_POST_LIKED = 'list_threads_v3_post_liked';//是否点赞 user_id
    public const LIST_THREADS_V3_POST_FAVOR = 'list_threads_v3_post_favor';//是否收藏 user_id
    public const LIST_THREADS_V3_POST_USERS = 'list_threads_v3_post_users';//帖子卡面底部的点赞支付摘要 thread_id



}
