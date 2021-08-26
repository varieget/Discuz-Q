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
 *                    @OA\Schema(ref = "#/components/schemas/dzq_thread")
 *                }))
 *            }
 *        )
 *    )
 *)
 */
