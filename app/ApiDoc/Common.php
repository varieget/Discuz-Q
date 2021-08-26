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
 *
 * @OA\Schema(
 *     schema="dzq_thread",
 *     title="全局的帖子详情",
 *     @OA\Property(property = "pageData", type = "array",@OA\Items(type = "object",
 *                        @OA\Property(property = "threadId", type = "integer", description = "帖子id"),
 *                        @OA\Property(property = "postId", type = "integer", description = "正文id"),
 *                        @OA\Property(property = "userId", type = "integer", description = "用户id"),
 *                        @OA\Property(property = "parentCategoryId", type = "integer", description = "分类id"),
 *                        @OA\Property(property = "topicId", type = "integer", description = "帖子归属的话题"),
 *                        @OA\Property(property = "categoryName", type = "string", description = "分类名称"),
 *                        @OA\Property(property = "parentCategoryName", type = "string", description = "父级分类名称"),
 *                        @OA\Property(property = "title", type = "string", description = "帖子标题"),
 *                        @OA\Property(property = "viewCount", type = "integer", description = "浏览数"),
 *                        @OA\Property(property = "isApproved", type = "boolean", description = "是否审核通过"),
 *                        @OA\Property(property = "isStick", type = "boolean", description = "是否设置置顶"),
 *                        @OA\Property(property = "isDraft", type = "boolean", description = "是否草稿"),
 *                        @OA\Property(property = "isSite", type = "boolean", description = "是否设置到付费站首页热点数据推荐列表"),
 *                        @OA\Property(property = "isAnonymous", type = "boolean", description = "是否匿名贴"),
 *                        @OA\Property(property = "isFavorite", type = "boolean", description = "当前用户是否收藏"),
 *                        @OA\Property(property = "price", type = "number",default=0, description = "帖子价格"),
 *
 *                        @OA\Property(property = "payType", type = "number",default = 0, description = "支付类型"),
 *
 *                        @OA\Property(property = "paid", type = "boolean", description = "是否已支付"),
 *                        @OA\Property(property = "isLike", type = "boolean", description = "当前用户是否点赞"),
 *                        @OA\Property(property = "isReward", type = "boolean", description = "当前用户是否打赏"),
 *                        @OA\Property(property = "createdAt", type = "string", description = "创建时间"),
 *                        @OA\Property(property = "updatedAt", type = "string", description = "更新时间"),
 *                        @OA\Property(property = "diffTime", type = "string",default="5秒前", description = "显示统一规则下的时间差"),
 *                        @OA\Property(property = "freewords", type = "number", description = "免费字数占比（0~1）"),
 *                        @OA\Property(property = "user", type = "object", description = "用户信息"),
 *                        @OA\Property(property = "group", type = "object", description = "用户组信息"),
 *                        @OA\Property(property = "likeReward", type = "object", description = "对该贴所有用户的点赞打赏信息"),
 *                        @OA\Property(property = "displayTag", type = "object", description = "帖子归属的所有标签"),
 *                        @OA\Property(property = "position", type = "object", description = "位置信息"),
 *                        @OA\Property(property = "ability", type = "object", description = "当前用户对该贴的操作权限")
 *                    ))
 *
 * )
 */
