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
 * @OA\Schema(
 *     schema="dzq_follow_item",
 *     title="关注/粉丝详情",
 *     @OA\Property(property = "pageData", type = "array", @OA\Items(type = "object", ref="#/components/schemas/dzq_follow"))
 * )
 *
 * @OA\Schema(
 *     schema="dzq_follow",
 *     title="关注/粉丝用户详情",
 *     @OA\Property(property = "group", type = "object", ref="#/components/schemas/dzq_group_simple_detail"),
 *     @OA\Property(property = "user", type = "object", ref="#/components/schemas/dzq_user_simple_detail"),
 *     @OA\Property(property = "userFollow", type = "object",  ref="#/components/schemas/dzq_user_follow_simple_detail"),
 * )
 *
 * @OA\Schema(
 *     schema="dzq_user_follow_simple_detail",
 *     title="关注/粉丝用户详情",
 *     @OA\Property(property = "createdAt", type="string", description = "创建时间", format = "datetime"),
 *     @OA\Property(property = "fromUserId", type="integer", description = "关注人id"),
 *     @OA\Property(property = "id", type="integer", description = "关注id"),
 *     @OA\Property(property = "isFollow", type="integer", description = "是否关注过别人/被别人关注过"),
 *     @OA\Property(property = "isMutual", type="integer", description = "是否互关"),
 *     @OA\Property(property = "toUserId", type="integer", description = "被关注人id"),
 *     @OA\Property(property = "updatedAt", type="string", format = "datetime", description = "更新时间"),
 * )
 *
 *
 */

