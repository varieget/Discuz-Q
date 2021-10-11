<?php
/**
 * @OA\Post(
 *     path="/plugin/activity/api/register/append",
 *     summary="参加报名活动",
 *     description="参加报名活动",
 *     tags={"插件"},
 *     @OA\Parameter(ref="#/components/parameters/bear_token"),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="activityId",type="integer",default=666,description="活动id"),
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="返回报名结果",
 *         @OA\JsonContent(ref="#/components/schemas/dzq_layout")
 *     )
 * )
 */
