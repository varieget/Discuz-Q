<?php
/**
 * @OA\Server(url="https://discuz.chat",description="Discuz! Q 官方网站")
 * @OA\Server(url="https://www.techo.chat",description="Discuz! Q 体验站")
 *
 * @OA\Info(
 *     title="Discuz! Q后台接口文档",
 *     version="3.0",
 *     description="本文档适用于对Discuz! Q进行二开的用户参考使用",
 *     termsOfService="https://gitee.com/Discuz/Discuz-Q",
 *     @OA\Contact(email="coralchu@tencent.com"),
 *     @OA\License(name="Apache 2.0",url="https://discuz.com/docs")
 * )
 * @OA\Tag(
 *     name="发布与展示",
 *     description="帖子相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 * @OA\Tag(
 *     name="注册登录",
 *     description="登录注册相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 * @OA\Tag(
 *     name="个人中心",
 *     description="管理后台相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 * @OA\Tag(
 *     name="支付钱包",
 *     description="钱包相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 * @OA\Tag(
 *     name="管理后台",
 *     description="管理后台相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="dzq_layout",
 *     title="接口返回",
 *     @OA\Property(property="Code",type="integer",description="dzq错误码"),
 *     @OA\Property(property="Message",type="string",description="错误描述信息"),
 *     @OA\Property(property="Data",description="api数据集",type="array",@OA\Items()),
 *     @OA\Property(property="RequestId",type="string",description="请求ID"),
 *     @OA\Property(property="RequestTime",format="datetime",default="2021-02-02 02:22:22", type="string",description="请求时间"),
 *     description="dzq接口的整体返回规范,Code等于0表示接口正常返回"
 * )
 * @OA\Schema(
 *     schema = "dzq_pagination",
 *     title = "分页接口模板",
 *     @OA\Property(property="pageData",type="array",description="分页数据",@OA\Items()),
 *     @OA\Property(property="currentPage",type="integer",format="number", default=1, description="当前页码"),
 *     @OA\Property(property="perPage",type="integer",format="number", default=20, description="每页数据条数"),
 *     @OA\Property(property="firstPageUrl",type="string",description="第一页数据地址"),
 *     @OA\Property(property="nextPageUrl",type="string",description="下一页数据地址"),
 *     @OA\Property(property="prePageUrl",type="string",description="上一页数据地址"),
 *     @OA\Property(property="pageLength",type="integer",description="页总数"),
 *     @OA\Property(property="totalCount",type="integer",description="全部数据条数"),
 *     @OA\Property(property="totalPage",type="integer",description="全部数据页数"),
 *     description="分页数据标准格式",
 * )
 * @OA\Parameter(
 *     parameter="bear_token",
 *     name="Authorization",
 *     in="header",
 *     required=true,
 *     description="Bearer Token",
 *     @OA\Schema(type="string")
 * )
 * @OA\Parameter(
 *    parameter="threadlist_page",
 *    name="page",
 *    in="query",
 *    required=false,
 *    description = "当前页",
 *    @OA\Schema(
 *        type="integer",default=1
 *    ),
 *),
 *@OA\Parameter(
 *     parameter="threadlist_perPage",
 *    name="perPage",
 *    in="query",
 *    required=false,
 *    description = "每页数据条数",
 *    @OA\Schema(
 *        type="integer",default=20
 *    ),
 *),
 *@OA\Parameter(
 *     parameter="threadlist_scope",
 *    name="scope",
 *    in="query",
 *    required=false,
 *    description = "列表所属模块域 0:普通 1：推荐 2：付费首页 3：搜索页",
 *    @OA\Schema(
 *        type="integer",
 *        enum={0,1,2,3},
 *        default=0
 *    )
 *),
 *@OA\Parameter(
 *     parameter="threadlist_essence",
 *    name="filter[essence]",
 *    in="query",
 *    required=false,
 *    description = "精华帖",
 *    @OA\Schema(
 *        type="integer",default=1
 *    )
 *),
 *@OA\Parameter(
 *     parameter="threadlist_types",
 *    name="filter[types][]",
 *    in="query",
 *    required=false,
 *    description = "帖子类型",
 *    @OA\Schema(type="array",@OA\Items(type="integer"))
 *),
 *@OA\Parameter(
 *     parameter="threadlist_sort",
 *    name="filter[sort]",
 *    in="query",
 *    required=false,
 *    description = "排序规则",
 *    @OA\Schema(
 *        type="integer",enum={1,2,3,4},default=1
 *    )
 *),
 *@OA\Parameter(
 *     parameter="threadlist_attention",
 *    name="filter[attention]",
 *    in="query",
 *    required=false,
 *    description = "是否关注",
 *    @OA\Schema(
 *        type="integer",enum={0,1},default=0
 *    )
 *),
 *@OA\Parameter(
 *     parameter="threadlist_complex",
 *    name="filter[complex]",
 *    in="query",
 *    required=false,
 *    description = "其他复合筛选类型 1:我的草稿 2:我的点赞 3:我的收藏 4:我的购买 5:我or他的主题页",
 *    @OA\Schema(
 *        type="integer",enum={1,2,3,4,5}
 *    )
 *),
 *@OA\Parameter(
 *     parameter="threadlist_exclusiveIds",
 *    name="filter[exclusiveIds][]",
 *    in="query",
 *    required=false,
 *    description = "需要过滤掉的帖子id集合",
 *    @OA\Schema(type="array",@OA\Items(type="integer"))
 *),
 *@OA\Parameter(
 *     parameter="threadlist_categoryids",
 *    name="filter[categoryids]",
 *    in="query",
 *    required=false,
 *    description = "分类组合（需要查询的分类id集合）",
 *    @OA\Schema(
 *        type="array",@OA\Items(type="integer")
 *    )
 *),
 * @OA\Schema(
 *     schema="local_plugin_output",
 *     title="本地帖子插件",
 *     description="帖子插件个性化的出参",
 *     @OA\Property(property = "body", type = "array", description = "插件个性化数据",@OA\Items(type="object")),
 *     @OA\Property(property = "operation", type = "string", description = "操作类型"),
 *     @OA\Property(property = "threadId", type = "integer", description = "帖子id"),
 *     @OA\Property(property = "tomId", type = "integer", description = "帖子插件id")
 * )
 * @OA\Schema(
 *     schema="local_plugin_input",
 *     title="本地帖子插件",
 *     description="帖子插件个性化的入参",
 *     @OA\Property(property = "body", type = "array", description = "插件个性化数据",@OA\Items(type="object")),
 *     @OA\Property(property = "operation", type = "string", description = "操作类型"),
 *     @OA\Property(property = "tomId", type = "integer", description = "帖子插件id")
 * )
 * @OA\Schema(
 *     schema="dzq_thread_item",
 *     title="全局的帖子详情",
 *     @OA\Property(property = "pageData", type = "array",@OA\Items(type = "object",ref="#/components/schemas/dzq_thread"))
 *
 * )
 *
 *
 * @OA\Schema(
 *     schema="dzq_thread",
 *     title="全局的帖子详情",
 *     @OA\Property(property = "threadId", type = "integer", description = "帖子id"),
 *     @OA\Property(property = "postId", type = "integer", description = "正文id"),
 *     @OA\Property(property = "userId", type = "integer", description = "用户id"),
 *     @OA\Property(property = "parentCategoryId", type = "integer", description = "分类id"),
 *     @OA\Property(property = "topicId", type = "integer", description = "帖子归属的话题"),
 *     @OA\Property(property = "categoryName", type = "string", description = "分类名称"),
 *     @OA\Property(property = "parentCategoryName", type = "string", description = "父级分类名称"),
 *     @OA\Property(property = "title", type = "string", description = "帖子标题"),
 *     @OA\Property(property = "viewCount", type = "integer", description = "浏览数"),
 *     @OA\Property(property = "isApproved", type = "boolean", description = "是否审核通过"),
 *     @OA\Property(property = "isStick", type = "boolean", description = "是否设置置顶"),
 *     @OA\Property(property = "isDraft", type = "boolean", description = "是否草稿"),
 *     @OA\Property(property = "isSite", type = "boolean", description = "是否设置到付费站首页热点数据推荐列表"),
 *     @OA\Property(property = "isAnonymous", type = "boolean", description = "是否匿名贴"),
 *     @OA\Property(property = "isFavorite", type = "boolean", description = "当前用户是否收藏"),
 *     @OA\Property(property = "price", type = "number",default=0, description = "帖子价格"),
 *     @OA\Property(property = "payType", type = "number",default = 0, description = "支付类型"),
 *     @OA\Property(property = "paid", type = "boolean", description = "是否已支付"),
 *     @OA\Property(property = "isLike", type = "boolean", description = "当前用户是否点赞"),
 *     @OA\Property(property = "isReward", type = "boolean", description = "当前用户是否打赏"),
 *     @OA\Property(property = "createdAt", type = "string", description = "创建时间"),
 *     @OA\Property(property = "updatedAt", type = "string", description = "更新时间"),
 *     @OA\Property(property = "diffTime", type = "string",default="5秒前", description = "显示统一规则下的时间差"),
 *     @OA\Property(property = "freewords", type = "number", description = "免费字数占比（0~1）"),
 *     @OA\Property(property = "user", type = "object", description = "用户信息",
 *          @OA\Property(property = "userId", type = "integer", description = "用户id"),
 *          @OA\Property(property = "nickname", type = "string", description = "昵称"),
 *          @OA\Property(property = "avatar", type = "string", description = "头像地址"),
 *          @OA\Property(property = "threadCount", type = "integer", description = "发帖总数"),
 *          @OA\Property(property = "followCount", type = "integer", description = "关注人数"),
 *          @OA\Property(property = "fansCount", type = "integer", description = "粉丝数"),
 *          @OA\Property(property = "questionCount", type = "integer", description = "问答数"),
 *          @OA\Property(property = "isRealName", type = "boolean", description = "是否实名"),
 *          @OA\Property(property = "joinedAt", type = "string", description = "加入时间")
 *     ),
 *     @OA\Property(property = "group", type = "object", description = "用户组信息",
 *          @OA\Property(property = "groupIcon", type = "string", description = "用户组图标（暂时没有用处）"),
 *          @OA\Property(property = "groupId", type = "integer", description = "群组id"),
 *          @OA\Property(property = "groupName", type = "string", description = "群组名称"),
 *          @OA\Property(property = "isDisplay", type = "boolean", description = "是否显示用户组名称"),
 *     ),
 *     @OA\Property(property = "likeReward", type = "object", description = "对该贴所有用户的点赞打赏信息",
 *         @OA\Property(property = "likePayCount", type = "integer", description = "点赞和支付的用户数"),
 *         @OA\Property(property = "postCount", type = "integer", description = "评论数"),
 *         @OA\Property(property = "shareCount", type = "integer", description = "分享数"),
 *         @OA\Property(property = "users", type = "array", description = "帖子卡片左下角显示的头像信息",@OA\Items(
 *               @OA\Property(property = "avatar", type = "integer", description = "头像地址"),
 *               @OA\Property(property = "createdAt", type = "integer", description = "用户创建时间"),
 *               @OA\Property(property = "nickname", type = "integer", description = "昵称"),
 *               @OA\Property(property = "type", type = "integer", description = "用户类型"),
 *               @OA\Property(property = "userId", type = "integer", description = "用户id")
 *               )),
 *          ),
 *     @OA\Property(property = "displayTag", type = "object", description = "帖子归属的所有标签",
 *         @OA\Property(property = "isEssence", type = "boolean", description = "精华贴"),
 *         @OA\Property(property = "isPrice", type = "boolean", description = "付费贴"),
 *         @OA\Property(property = "isRedPack", type = "boolean", description = "红包贴"),
 *         @OA\Property(property = "isReward", type = "boolean", description = "悬赏贴"),
 *         @OA\Property(property = "isVote", type = "boolean", description = "投票贴"),
 *     ),
 *     @OA\Property(property = "position", type = "object", description = "位置信息",
 *        @OA\Property(property = "address", type = "string", description = "街道详细地址"),
 *        @OA\Property(property = "latitude", type = "boolean", description = "纬度"),
 *        @OA\Property(property = "location", type = "boolean", description = "地址"),
 *        @OA\Property(property = "longitude", type = "boolean", description = "经度"),
 *      ),
 *     @OA\Property(property = "ability", type = "object", description = "当前用户对该贴的操作权限",
 *        @OA\Property(property = "canBeReward", type = "boolean", description = "是否可打赏"),
 *        @OA\Property(property = "canDelete", type = "boolean", description = "是否可删除帖子"),
 *        @OA\Property(property = "canEdit", type = "boolean", description = "是否可编辑"),
 *        @OA\Property(property = "canEssence", type = "boolean", description = "是否可设置精华"),
 *        @OA\Property(property = "canFreeViewPost", type = "boolean", description = "是否免费查看付费帖详情"),
 *        @OA\Property(property = "canReply", type = "boolean", description = "是否可回复"),
 *        @OA\Property(property = "canStick", type = "boolean", description = "是否可设置置顶"),
 *        @OA\Property(property = "canViewPost", type = "boolean", description = "是否可查看详情"),
 *     ),
 *     @OA\Property(property = "content", type = "object", description = "帖子正文内容",
 *        @OA\Property(property = "text", type = "string", description = "帖子正文内容"),
 *        @OA\Property(property = "indexes", type = "object", description = "是否可删除帖子",
 *        @OA\Property(property = "101", type = "object", description = "图片",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "102", type = "object", description = "语音",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "103", type = "string", description = "视频",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "104", type = "string", description = "商品",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "106", type = "string", description = "红包",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "107", type = "string", description = "悬赏",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "108", type = "string", description = "文件附件",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "109", type = "string", description = "投票",ref="#/components/schemas/local_plugin_output"),
 *      )))
 * )
 */
