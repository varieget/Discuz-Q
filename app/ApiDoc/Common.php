<?php
/**
 * https://editor.swagger.io/
 * https://petstore.swagger.io/
 * https://github.com/zircote/swagger-php
 *
 * https://zircote.github.io/swagger-php/Getting-started.html#installation
 *
 * https://packagist.org/packages/zircote/swagger-php
 * https://hub.docker.com/r/swaggerapi/swagger-ui
 * https://hub.docker.com/r/swaggerapi/swagger-editor
 *
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
 *     description="主题和评论相关接口",
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
 *     description="个人中心相关接口",
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
 * @OA\Tag(
 *     name="私信与消息",
 *     description="用户私信、消息通知相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 * @OA\Tag(
 *     name="附件",
 *     description="附件相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 *  * @OA\Tag(
 *     name="邀请",
 *     description="邀请件相关接口",
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
 *     @OA\Property(property="Data",description="api数据集",type="object"),
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
 *     required=false,
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
 *     title="主题插件输出参数",
 *     description="帖子插件个性化的出参",
 *     @OA\Property(property = "body", type = "array", description = "插件个性化数据",@OA\Items(type="object")),
 *     @OA\Property(property = "operation", type = "string", description = "操作类型"),
 *     @OA\Property(property = "threadId", type = "integer", description = "帖子id"),
 *     @OA\Property(property = "tomId", type = "integer", description = "帖子插件id")
 * )
 * @OA\Schema(
 *     schema="local_plugin_input",
 *     title="主题插件输入参数",
 *     description="帖子插件个性化的入参",
 *     @OA\Property(property = "body", type = "array", description = "插件个性化数据",@OA\Items(type="object")),
 *     @OA\Property(property = "operation", type = "string", description = "操作类型"),
 *     @OA\Property(property = "tomId", type = "integer", description = "帖子插件id")
 * )
 * @OA\Schema(
 *     schema="dzq_thread_item",
 *     title="主题详情集合",
 *     @OA\Property(property = "pageData", type = "array",@OA\Items(type = "object",ref="#/components/schemas/dzq_thread"))
 *
 * )
 *
 *
 * @OA\Schema(
 *     schema="dzq_thread",
 *     title="单个主题详情",
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
 *     @OA\Property(property = "userStickStatus", type = "number", enum={0,1}, description = "是否在个人中心置顶（1置顶0不置顶）"),
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
 *     @OA\Property(property = "content", type = "object", description = "帖子正文内容",allOf={
 *         @OA\Schema(@OA\Property(property = "text", type = "string", description = "帖子正文内容")),
 *         @OA\Schema(ref="#/components/schemas/thread_indexes_input")
 *     })
 * )
 * @OA\Schema(
 *     schema="thread_indexes_input",
 *     title="主题内含基础插件信息输入参数",
 *     @OA\Property(property="indexes",type="object",description="插件数据集合",
 *        @OA\Property(property = "101", type = "object", description = "图片",ref="#/components/schemas/local_plugin_input"),
 *        @OA\Property(property = "102", type = "object", description = "语音",ref="#/components/schemas/local_plugin_input"),
 *        @OA\Property(property = "103", type = "string", description = "视频",ref="#/components/schemas/local_plugin_input"),
 *        @OA\Property(property = "104", type = "string", description = "商品",ref="#/components/schemas/local_plugin_input"),
 *        @OA\Property(property = "106", type = "string", description = "红包",ref="#/components/schemas/local_plugin_input"),
 *        @OA\Property(property = "107", type = "string", description = "悬赏",ref="#/components/schemas/local_plugin_input"),
 *        @OA\Property(property = "108", type = "string", description = "文件附件",ref="#/components/schemas/local_plugin_input"),
 *        @OA\Property(property = "109", type = "string", description = "投票",ref="#/components/schemas/local_plugin_input"),
 *     )
 * )
 * @OA\Schema(
 *     schema="thread_indexes_output",
 *     title="主题内含基础插件信息输出参数",
 *     @OA\Property(property="indexes",type="object",description="插件数据集合",
 *        @OA\Property(property = "101", type = "object", description = "图片",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "102", type = "object", description = "语音",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "103", type = "string", description = "视频",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "104", type = "string", description = "商品",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "106", type = "string", description = "红包",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "107", type = "string", description = "悬赏",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "108", type = "string", description = "文件附件",ref="#/components/schemas/local_plugin_output"),
 *        @OA\Property(property = "109", type = "string", description = "投票",ref="#/components/schemas/local_plugin_output"),
 *     )
 * ),
 *
 * @OA\Parameter(
 *     parameter="page",
 *     name="page",
 *     in="query",
 *     required=false,
 *     description="当前页",
 *     @OA\Schema(
 *          type="integer",
 *          default=1
 *      )
 * ),
 *
 * @OA\Parameter(
 *     parameter="perPage",
 *     name="perPage",
 *     in="query",
 *     required=false,
 *     description="当前页",
 *     @OA\Schema(
 *          type="integer",
 *          default=20
 *      )
 * ),
 *
 * @OA\Parameter(
 *     parameter="filter_userId",
 *     name="filter[userId]",
 *     in="query",
 *     required=false,
 *     description = "筛选用户id",
 *     @OA\Schema(
 *          type="integer", default=1
 *      )
 * ),
 *
 * @OA\Parameter(
 *     parameter="filter_type",
 *     name="filter[type]",
 *     in="query",
 *     required=false,
 *     description="筛选类型",
 *     @OA\Schema(
 *          type="integer", default = 1
 *      )
 * ),
 *
 *
 * @OA\Schema(
 *     schema = "post_detail_output",
 *     title = "评论详情输出数据集合",
 *          @OA\Property(property = "id", type = "integer", description = "评论id"),
 *          @OA\Property(property = "userId", type = "integer", description = "评论作者id"),
 *          @OA\Property(property = "replyPostId", type = "integer", description = "最新回复id"),
 *          @OA\Property(property = "replyUserId", type = "integer", description = "最新回复作者id"),
 *          @OA\Property(property = "commentPostId", type = "integer", description = "评论回复id"),
 *          @OA\Property(property = "commentUserId", type = "integer", description = "评论回复作者id"),
 *          @OA\Property(property = "summaryText", type = "string", description = "评论摘要"),
 *          @OA\Property(property = "content", type = "string", description = "评论内容"),
 *          @OA\Property(property = "replyCount", type = "integer", description = "关联回复数"),
 *          @OA\Property(property = "likeCount", type = "integer", description = "点赞数"),
 *          @OA\Property(property = "createdAt", type = "string", description = "创建时间"),
 *          @OA\Property(property = "updatedAt", type = "string", description = "更新时间"),
 *          @OA\Property(property = "isApproved", type = "integer", description = "是否已审核(0审核中，1正常)", enum = {0, 1}),
 *          @OA\Property(property = "canApprove", type = "boolean", description = "是否可审核"),
 *          @OA\Property(property = "canDelete", type = "boolean", description = "是否可删除"),
 *          @OA\Property(property = "canHide", type = "boolean", description = "是否可删除"),
 *          @OA\Property(property = "contentAttachIds", type = "array", description = "内容附件id", @OA\Items()),
 *          @OA\Property(property = "parseContentHtml", type = "string", description = "评论内容-html"),
 *          @OA\Property(property = "ip", type = "string", description = "ip地址"),
 *          @OA\Property(property = "port", type = "integer", description = "端口"),
 *          @OA\Property(property = "isDeleted", type = "boolean", description = "是否已删除"),
 *          @OA\Property(property = "isFirst", type = "boolean", description = "是否首个回复"),
 *          @OA\Property(property = "isComment", type = "boolean", description = "是否是回复回帖的内容"),
 *          @OA\Property(property = "isLiked", type = "boolean", description = "是否已点赞"),
 *          @OA\Property(property = "user", type = "object", description = "评论作者信息", allOf = {@OA\Schema(ref = "#/components/schemas/user_detail_output")}),
 *          @OA\Property(property = "replyUser", type = "object", description = "回复作者信息", allOf = {@OA\Schema(ref = "#/components/schemas/user_detail_output")}),
 *          @OA\Property(property = "commentUser", type = "object", description = "评论回复作者信息", allOf = {@OA\Schema(ref = "#/components/schemas/user_detail_output")}),
 *          @OA\Property(property = "attachments", type = "array", description = "评论图片信息", @OA\Items(ref = "#/components/schemas/attachment_detail_output"))
 * )
 * @OA\Schema(
 *     schema = "attachment_detail_output",
 *     title = "附件详情输出数据集合",
 *          @OA\Property(property = "id", type = "integer", description = "附件id"),
 *          @OA\Property(property = "userId", type = "integer", description = "附件作者id"),
 *          @OA\Property(property = "order", type = "integer", description = "附件排序"),
 *          @OA\Property(property = "type", type = "integer", description = "附件类型(0帖子附件，1帖子图片，2帖子音频，3帖子视频，4消息图片)", enum = {0, 1, 2, 3, 4}),
 *          @OA\Property(property = "type_id", type = "integer", description = "关联的类型id(thread_id,post_id,dialog_message_id)"),
 *          @OA\Property(property = "isRemote", type = "boolean", description = "是否远程附件"),
 *          @OA\Property(property = "isApproved", type = "integer", description = "附件审核状态"),
 *          @OA\Property(property = "url", type = "string", description = "链接"),
 *          @OA\Property(property = "attachment", type = "string", description = "附件存储别名"),
 *          @OA\Property(property = "extension", type = "string", description = "附件后缀"),
 *          @OA\Property(property = "fileName", type = "string", description = "附件名"),
 *          @OA\Property(property = "filePath", type = "string", description = "附件存储路径"),
 *          @OA\Property(property = "fileSize", type = "integer", description = "附件大小"),
 *          @OA\Property(property = "fileType", type = "string", description = "附件mimeType"),
 *          @OA\Property(property = "fileWidth", type = "integer", description = "图-宽"),
 *          @OA\Property(property = "fileHeight", type = "integer", description = "图-高"),
 *          @OA\Property(property = "thumbUrl", type = "string", description = "缩略图链接")
 * )
 * @OA\Schema(
 *     schema = "user_detail_output",
 *     title = "用户详情输出数据集合",
 *          @OA\Property(property = "id", type = "integer", description = "用户id"),
 *          @OA\Property(property = "username", type = "string", description = "用户名"),
 *          @OA\Property(property = "nickname", type = "string", description = "昵称"),
 *          @OA\Property(property = "mobile", type = "string", description = "昵称"),
 *          @OA\Property(property = "avatar", type = "string", description = "头像地址"),
 *          @OA\Property(property = "avatarUrl", type = "string", description = "头像地址"),
 *          @OA\Property(property = "realname", type = "string", description = "身份证姓名"),
 *          @OA\Property(property = "identity", type = "string", description = "身份证号码"),
 *          @OA\Property(property = "threadCount", type = "integer", description = "主题数"),
 *          @OA\Property(property = "followCount", type = "integer", description = "关注数"),
 *          @OA\Property(property = "fansCount", type = "integer", description = "粉丝数"),
 *          @OA\Property(property = "likedCount", type = "integer", description = "点赞数"),
 *          @OA\Property(property = "updatedAt", type = "string", description = "更新时间"),
 *          @OA\Property(property = "createdAt", type = "string", description = "创建时间")
 * )
 * @OA\Schema(
 *     schema = "user_wallet_detail_output",
 *     title = "用户钱包详情输出数据集合",
 *          @OA\Property(property = "userId", type = "integer", description = "用户id"),
 *          @OA\Property(property = "availableAmount", type = "string", description = "钱包可用余额"),
 *          @OA\Property(property = "freezeAmount", type = "string", description = "钱包冻结金额"),
 *          @OA\Property(property = "walletStatus", type = "integer", description = "钱包状态(0正常，1冻结体现)", enum = {0, 1}),
 *          @OA\Property(property = "createdAt", type = "string", description = "创建时间"),
 *          @OA\Property(property = "updatedAt", type = "string", description = "更新时间"),
 *          @OA\Property(property = "cashTaxRatio", type = "string", description = "用户提现时的税率")
 * )
 * @OA\Parameter(
 *     parameter = "notification_type_detail",
 *     name = "type",
 *     in = "query",
 *     required = true,
 *     description = "system系统通知, rewarded财务通知, threadrewarded悬赏通知, receiveredpacket红包通知, threadrewardedexpired悬赏过期通知, related艾特@我的, replied回复我的, liked点赞通知",
 *     @OA\Schema(
 *        type = "string",enum = {"system", "rewarded", "threadrewarded", "receiveredpacket", "threadrewardedexpired", "related", "replied", "liked"}
 *    )
 *),
 * @OA\Schema(
 *     schema = "notification_item",
 *     title = "消息通知详情集合",
 *     @OA\Property(property = "pageData", type = "array", @OA\Items(type = "object", ref = "#/components/schemas/notification_detail_output"))
 *
 * )
 * @OA\Schema(
 *     schema = "notification_detail_output",
 *     title = "消息通知详情输出数据集合",
 *           @OA\Property(property = "id", type = "integer", description = "消息id"),
 *           @OA\Property(property = "type", type = "string", description = "消息类型", enum = {"system", "rewarded", "threadrewarded", "receiveredpacket", "threadrewardedexpired", "related", "replied", "liked"}),
 *           @OA\Property(property = "title", type = "string", description = "消息通知标题"),
 *           @OA\Property(property = "content", type = "string", description = "消息通知内容"),
 *           @OA\Property(property = "raw", type = "object", description = "消息模板", allOf = {
 *              @OA\Schema(@OA\Property(property = "tplId", type = "integer", description = "消息模板id"))
 *           }),
 *           @OA\Property(property = "userId", type = "integer", description = "发送人-用户id"),
 *           @OA\Property(property = "username", type = "string", description = "发送人-用户名"),
 *           @OA\Property(property = "userAvatar", type = "string", description = "发送人-用户头像"),
 *           @OA\Property(property = "nickname", type = "string", description = "发送人-昵称"),
 *           @OA\Property(property = "isReal", type = "boolean", description = "是否实名"),
 *           @OA\Property(property = "readAt", type = "integer", description = "已读时间"),
 *           @OA\Property(property = "createdAt", type = "string", description = "发送时间"),
 *           @OA\Property(property = "threadId", type = "integer", description = "帖子id"),
 *           @OA\Property(property = "threadTitle", type = "string", description = "帖子标题"),
 *           @OA\Property(property = "threadUsername", type = "string", description = "帖子作者用户名"),
 *           @OA\Property(property = "threadUserGroups", type = "string", description = "帖子作者所在用户组"),
 *           @OA\Property(property = "threadIsApproved", type = "integer", description = "帖子是否已审核"),
 *           @OA\Property(property = "threadUserNickname", type = "string", description = "帖子作者昵称"),
 *           @OA\Property(property = "threadUserAvatar", type = "string", description = "帖子作者头像"),
 *           @OA\Property(property = "threadCreatedAt", type = "string", description = "帖子创建时间"),
 *           @OA\Property(property = "postId", type = "integer", description = "内容id"),
 *           @OA\Property(property = "postContent", type = "string", description = "内容"),
 *           @OA\Property(property = "postCreatedAt", type = "string", description = "内容创建时间"),
 *           @OA\Property(property = "isFirst", type = "boolean", description = "是否是首帖内容"),
 *           @OA\Property(property = "replyPostId", type = "integer", description = "楼中楼回复id"),
 *           @OA\Property(property = "replyPostUserId", type = "integer", description = "楼中楼回复-用户id"),
 *           @OA\Property(property = "replyPostUserName", type = "string", description = "楼中楼回复-用户-用户名"),
 *           @OA\Property(property = "replyPostContent", type = "string", description = "楼中楼回复内容"),
 *           @OA\Property(property = "replyPostCreatedAt", type = "string", description = "楼中楼回复时间"),
 *           @OA\Property(property = "isReply", type = "integer", description = "是否已回复")
 * )
 * @OA\Schema(
 *     schema = "dialog_message_detail_output",
 *     title = "私信详情输出数据集合",
 *           @OA\Property(property = "id", type = "integer", description = "私信id"),
 *           @OA\Property(property = "userId", type = "integer", description = "用户id"),
 *           @OA\Property(property = "unreadCount", type = "integer", description = "未读数"),
 *           @OA\Property(property = "dialogId", type = "integer", description = "对话id"),
 *           @OA\Property(property = "attachmentId", type = "integer", description = "附件id"),
 *           @OA\Property(property = "summary", type = "string", description = "私信内容摘要"),
 *           @OA\Property(property = "messageText", type = "string", description = "私信文字内容"),
 *           @OA\Property(property = "messageTextHtml", type = "string", description = "私信文字网页内容"),
 *           @OA\Property(property = "imageUrl", type = "string", description = "私信图片链接"),
 *           @OA\Property(property = "updatedAt", type = "string", description = "更新时间"),
 *           @OA\Property(property = "createdAt", type = "string", description = "创建时间")
 * )
 *
 * @OA\Schema(
 *     schema="dzq_qrcode",
 *     title="返回二维码相关信息",
 *     @OA\Property(property="Data",type="object",
 *         @OA\Property(property = "sessionToken", type = "string", description = "用户sessionToken"),
 *         @OA\Property(property = "base64Img", type = "string", description = "二维码"),
 *     ))
 * )
 *
 * @OA\Schema(
 *     schema="dzq_login_token",
 *     title="登录态相关信息",
 *     @OA\Property(property="Data",type="object",
 *         @OA\Property(property = "tokenType", type = "string", description = "token类型"),
 *         @OA\Property(property = "expiresIn", type = "integer", description = "过期时间(秒)"),
 *         @OA\Property(property = "accessToken", type = "string", description = "token类型"),
 *         @OA\Property(property = "refreshToken", type = "string", description = "token类型"),
 *         @OA\Property(property = "isMissNickname", type = "integer", description = "是否缺少昵称"),
 *         @OA\Property(property = "avatarUrl", type = "string", description = "头像url"),
 *         @OA\Property(property = "userStatus", type = "integer", description = "用户状态"),
 *         @OA\Property(property = "uid", type = "integer", description = "用户id"),
 *     ))
 * )
 *
 * @OA\Schema(
 *     schema="dzq_user_info",
 *     title="用户相关信息",
 *     @OA\Property(property = "Data", type = "object",ref="#/components/schemas/dzq_user_model")
 * )
 *
 * @OA\Schema(
 *     schema="dzq_user_model",
 *     title="用户表数据",
 *     @OA\Property(property = "id", type = "integer", description = "用户id"),
 *     @OA\Property(property = "username", type = "string", description = "用户名"),
 *     @OA\Property(property = "password", type = "string", description = "密码"),
 *     @OA\Property(property = "nickname", type = "string", description = "用户昵称"),
 *     @OA\Property(property = "payPassword", type = "string", description = "支付密码"),
 *     @OA\Property(property = "mobile", type = "string", description = "手机号"),
 *     @OA\Property(property = "signature", type = "string", description = "签名"),
 *     @OA\Property(property = "lastLoginIp", type = "string", description = "最后登录ip地址"),
 *     @OA\Property(property = "lastLoginPort", type = "integer", description = "最后登录端口"),
 *     @OA\Property(property = "registerIp", type = "string", description = "注册ip"),
 *     @OA\Property(property = "registerPort", type = "string", description = "注册端口"),
 *     @OA\Property(property = "registerReason", type = "string", description = "注册原因"),
 *     @OA\Property(property = "rejectReason", type = "string", description = "审核拒绝原因"),
 *     @OA\Property(property = "usernameBout", type = "integer", description = "用户名修改次数"),
 *     @OA\Property(property = "threadCount", type = "integer", description = "主题数"),
 *     @OA\Property(property = "followCount", type = "integer", description = "关注数"),
 *     @OA\Property(property = "fansCount", type = "integer", description = "粉丝数"),
 *     @OA\Property(property = "likedCount", type = "integer", description = "点赞数"),
 *     @OA\Property(property = "questionCount", type = "integer", description = "提问数"),
 *     @OA\Property(property = "status", type = "integer", description = "用户状态：0正常 1禁用 2审核中 3审核拒绝 4审核忽略"),
 *     @OA\Property(property = "avatar", type = "integer", description = "头像地址"),
 *     @OA\Property(property = "identity", type = "string", description = "身份证号码"),
 *     @OA\Property(property = "realname", type = "string", description = "身份证姓名"),
 *     @OA\Property(property = "avatarAt", type = "string", description = "头像修改时间"),
 *     @OA\Property(property = "loginAt", type = "string", description = "最后登录时间"),
 *     @OA\Property(property = "joinedAt", type = "string", description = "付费加入时间"),
 *     @OA\Property(property = "expiredAt", type = "string", description = "付费到期时间"),
 *     @OA\Property(property = "createdAt", type = "string", description = "创建时间"),
 *     @OA\Property(property = "updatedAt", type = "string", description = "更新时间"),
 *     @OA\Property(property = "bindType", type = "integer", description = "登录绑定类型；0：默认或微信；2：qq登录；"),
 * )
 *
 * @OA\Schema(
 *     schema="dzq_wechat_user_model",
 *     title="微信用户表数据",
 *     @OA\Property(property = "id", type = "integer", description = "自增长id"),
 *     @OA\Property(property = "userId", type = "integer", description = "用户id"),
 *     @OA\Property(property = "mpOpenid", type = "string", description = "公众号openid"),
 *     @OA\Property(property = "devOpenid", type = "string", description = "开放平台openid"),
 *     @OA\Property(property = "minOpenid", type = "string", description = "小程序openid"),
 *     @OA\Property(property = "nickname", type = "string", description = "微信昵称"),
 *     @OA\Property(property = "sex", type = "integer", description = "性别"),
 *     @OA\Property(property = "province", type = "string", description = "省份"),
 *     @OA\Property(property = "city", type = "string", description = "城市"),
 *     @OA\Property(property = "country", type = "string", description = "国家"),
 *     @OA\Property(property = "headimgurl", type = "string", description = "头像"),
 *     @OA\Property(property = "privilege", type = "string", description = "用户特权信息"),
 *     @OA\Property(property = "unionid", type = "string", description = "只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段"),
 *     @OA\Property(property = "createdAt", type = "string", description = "创建时间"),
 *     @OA\Property(property = "updatedAt", type = "string", description = "更新时间"),
 * )
 *
 *
 *
 *
 *
 *  * @OA\Schema(
 *     schema="dzq_post_detail",
 *     title="评论详情",
 *     @OA\Property(property = "canDelete", type = "boolean", description="能否删除"),
 *     @OA\Property(property = "canHide", type = "boolean", description="能否删除"),
 *     @OA\Property(property = "canLike", type = "boolean", description="能否点赞"),
 *     @OA\Property(property = "commentPostId", type = "integer", description="被评论id"),
 *     @OA\Property(property = "commentUserId", type = "integer", description="被评论用户id"),
 *     @OA\Property(property = "content", type = "string", description="评论内容"),
 *     @OA\Property(property = "createdAt", type = "string", description="评论时间"),
 *     @OA\Property(property = "id", type = "integer", description="评论id"),
 *     @OA\Property(property = "images", type = "array", description="图片url", @OA\Items(type="string")),
 *     @OA\Property(property = "isApproved", type = "integer", description="是否审核通过"),
 *     @OA\Property(property = "isComment", type = "boolean", description="是否是二级评论"),
 *     @OA\Property(property = "isDeleted", type = "boolean", description="是否被删除"),
 *     @OA\Property(property = "isFirst", type = "boolean", description="是否是帖子内容"),
 *     @OA\Property(property = "isLiked", type = "boolean", description="是否点赞"),
 *     @OA\Property(property = "likeCount", type = "integer", description="点赞数量"),
 *     @OA\Property(property = "likeState", type = "object", description="关联点赞详情",
 *          @OA\Property(property = "post_id", type="integer", description="点赞评论id"),
 *          @OA\Property(property = "user_id", type="integer", description="点赞用户id")
 *     ),
 *     @OA\Property(property = "likedAt", type = "string", description="点赞时间"),
 *     @OA\Property(property = "redPacketAmount", type = "number", description="红包金额"),
 *     @OA\Property(property = "replyCount", type = "integer", description="回复数量"),
 *     @OA\Property(property = "replyPostId", type = "integer", description="回复评论id"),
 *     @OA\Property(property = "replyUserId", type = "integer", description="回复用户id"),
 *     @OA\Property(property = "rewards", type = "number", description="获得悬赏金额"),
 *     @OA\Property(property = "summaryText", type = "string", description="评论简介"),
 *     @OA\Property(property = "threadId", type = "integer", description="帖子id"),
 *     @OA\Property(property = "user", type = "object", description="发评论用户信息",
 *          @OA\Property(property="avatar", type="string", description="用户头像url"),
 *          @OA\Property(property="id", type="integer", description="用户id"),
 *          @OA\Property(property="isReal", type="boolean", description="是否实名"),
 *          @OA\Property(property="nickname", type="string", description="用户昵称"),
 *          @OA\Property(property="username", type="string", description="用户名称"),
 *     ),
 *     @OA\Property(property = "userId", type = "integer", description="发帖用户id"),
 * )
 *  @OA\Parameter(
 *    parameter="filter_changeType",
 *    name="filter[changeType]",
 *    in="query",
 *    required=false,
 *    description = "收入类型",
 *    @OA\Schema(type="array",@OA\Items(type="integer"))
 *)
 * *  @OA\Parameter(
 *    parameter="filter_cashStatus",
 *    name="filter[cashStatus]",
 *    in="query",
 *    required=false,
 *    description = "提现状态",
 *    @OA\Schema(type="array",@OA\Items(type="integer"))
 *)
 *
 */
