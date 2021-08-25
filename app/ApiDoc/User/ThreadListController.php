<?php
/**
 *@OA\Get(
 *    path = "/apiv3/thread.list",
 *    summary = "帖子列表",
 *    description = "Discuz! Q 全站帖子列表统一接口，包括且不限于首页列表、搜索列表、个人中心列表、购买列表、付费站首页等",
 *    tags ={"发布与展示"},
 *    @OA\Parameter(ref = "#/components/parameters/bear_token"),
 *    @OA\Response(
 *        response = 200,
 *        description = "返回帖子列表",
 *        @OA\JsonContent(allOf ={
 *                @OA\Schema(ref = "#/components/schemas/dzq_layout"),
 *                @OA\Schema(@OA\Property(property = "Data", type = "object",allOf={
 *                    @OA\Schema(ref = "#/components/schemas/dzq_pagination"),
 *                    @OA\Schema(@OA\Property(property = "pageData", type = "array",@OA\Items(type = "object",
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
 *                    )))
 *                }))
 *            }
 *        )
 *    )
 *)
 */
