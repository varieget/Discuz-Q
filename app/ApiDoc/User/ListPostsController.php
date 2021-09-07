<?php
/**
 *@OA\Get(
 *    path = "/apiv3/posts",
 *    summary = "评论列表",
 *    description = "Discuz! Q 评论列表",
 *    tags ={"发布与展示"},
 *@OA\Parameter(ref = "#/components/parameters/bear_token"),
 *@OA\Parameter(ref = "#/components/parameters/page"),
 *@OA\Parameter(ref = "#/components/parameters/perPage"),
 *@OA\Parameter(
 *     name="filter[thread]",
 *     in="query",
 *     required=false,
 *     description="帖子id",
 *     @OA\Schema(
 *          type="integer",
 *          default=1
 *      )
 * ),
 *@OA\Parameter(
 *     name="sort",
 *     in="query",
 *     required=false,
 *     description="排序字段",
 *     @OA\Schema(
 *          type="string",
 *          default="createdAt"
 *      ),
 *),
 *
 * @OA\Response(
 *        response = 200,
 *        description = "返回关注/粉丝列表",
 *        @OA\JsonContent(allOf ={
 *                @OA\Schema(ref = "#/components/schemas/dzq_layout"),
 *                @OA\Schema(@OA\Property(property = "Data", type = "object",allOf={
 *                    @OA\Schema(ref = "#/components/schemas/dzq_pagination"),
 *                    @OA\Schema(ref = "#/components/schemas/dzq_post_list")
 *                }))
 *            }
 *        )
 *    )
 *)
 *
 * @OA\Schema(
 *     schema="dzq_post_list",
 *     title="评论列表",
 *     @OA\Property(property = "pageData", type = "array", @OA\Items(type = "object",
 *          allOf={
                @OA\Schema(ref="#/components/schemas/dzq_post_detail"),
 *              @OA\Schema(ref="#/components/schemas/dzq_post_detail_last_three_comments")
 *          }
 *      ))
 * )
 *
 * @OA\Schema(
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
 *
 * @OA\Schema(
 *     schema="dzq_post_detail_last_three_comments",
 *     title="最近3条评论",
 *     @OA\Property(property = "lastThreeComments", type="array", @OA\Items(type="object",
 *              ref="#/components/schemas/dzq_post_detail"
 * ))
 *
 * )
 *
 *
 */

