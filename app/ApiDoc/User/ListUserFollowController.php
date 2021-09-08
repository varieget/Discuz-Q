<?php
/**
 *@OA\Get(
 *    path = "/apiv3/follow",
 *    summary = "个人中心",
 *    description = "Discuz! Q 关注/粉丝列表",
 *    tags ={"个人中心"},
 *@OA\Parameter(ref = "#/components/parameters/bear_token"),
 *@OA\Parameter(ref = "#/components/parameters/page"),
 *@OA\Parameter(ref = "#/components/parameters/perPage"),
 *@OA\Parameter(ref = "#/components/parameters/filter_userId"),
 *@OA\Parameter(ref = "#/components/parameters/filter_type"),
 *
 * @OA\Response(
 *        response = 200,
 *        description = "返回关注/粉丝列表",
 *        @OA\JsonContent(allOf ={
 *                @OA\Schema(ref = "#/components/schemas/dzq_layout"),
 *                @OA\Schema(@OA\Property(property = "Data", type = "object",allOf={
 *                    @OA\Schema(ref = "#/components/schemas/dzq_pagination"),
 *                    @OA\Schema(ref = "#/components/schemas/dzq_follow_item")
 *                }))
 *            }
 *        )
 *    )
 *)
 *
 *
 *
 *
 */

