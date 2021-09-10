<?php
/**
 * @OA\Post(
 *     path="/apiv3/posts.reward",
 *     summary="帖子打赏",
 *     description="帖子打赏接口",
 *     tags={"发布与展示"},
 *     @OA\Parameter(ref="#/components/parameters/bear_token"),
 *     @OA\RequestBody(
 *         required=true,
 *         description = "帖子打赏",
 *         @OA\JsonContent(
 *             @OA\Property(property="thread_id",type="bigint", description ="帖子id")    ,
 *             @OA\Property(property="rewards",type="floor", description ="打赏数量"),
 *             @OA\Property(property="post_id",type="bigint", description ="评论id"),
 *             )
 *           ),
 * @OA\Response(response=200,
 *        description="帖子打赏接口返回",
 *        @OA\JsonContent(allOf ={
 *           @OA\Schema(ref = "#/components/schemas/dzq_layout"),
 *           @OA\Schema(title="帖子打赏返回数据",description="帖子打赏返回数据",@OA\Property(property="Data",type="object",
 *                @OA\Property(property="wait", type="string", description="待定")))
 *
 *
 *     })
 *     )
 * )
 *        )
 *     )

 */
