<?php
/**
 * @OA\Get(
 *     path = "/apiv3/posts.reply",
 *     summary = "查询单条评论的最新回复评论",
 *     description = "查询单条评论的最新回复评论",
 *     tags = {"发布与展示"},
 *     @OA\Parameter(ref = "#/components/parameters/bear_token"),
 *     @OA\Parameter(
 *        name = "pid",
 *        in = "query",
 *        required = true,
 *        description = "评论id",
 *        @OA\Schema(type = "integer")
 *     ),
 *     @OA\Response(
 *        response = 200,
 *        description = "返回评论最新回复数据",
 *        @OA\JsonContent(allOf = {
 *                @OA\Schema(ref = "#/components/schemas/dzq_layout"),
 *                @OA\Schema(@OA\Property(property = "Data", type = "object", allOf = {
 *                    @OA\Schema(ref = "#/components/schemas/post_detail_output")
 *                }))
 *            }
 *        )
 *     )
 * )
 */
