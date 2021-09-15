<?php
/**
 *@OA\Post(
 *    path = "/api/v3/posts.create",
 *    summary = "发表评论",
 *    description = "Discuz! Q 发表评论",
 *    tags ={"发布与展示"},
 *    @OA\Parameter(ref = "#/components/parameters/bear_token"),
 *     @OA\RequestBody(
 *        description = "发表评论",
 *        required=true,
 *        @OA\JsonContent(
 *           @OA\Property(property="attachments",type="array",description="附件", @OA\Items(type="string")),
 *           @OA\Property(property="content",type="string",description="评论内容"),
 *           @OA\Property(property="id",type="integer",description="帖子id"),
 *          @OA\Property(property="isComment", type="boolean", description="是否是评论"),
 *           @OA\Property(property="replyId", type="integer", description="评论id")
 *        ),
 *     ),
 *
 * @OA\Response(
 *        response = 200,
 *        description = "",
 *        @OA\JsonContent(allOf ={
 *                @OA\Schema(ref = "#/components/schemas/dzq_layout"),
 *                @OA\Schema(@OA\Property(property="Data",type="object", ref="#/components/schemas/dzq_post_detail"))
 *            }
 *        )
 *    )
 *)
 *
 *
 *
 *
 */

