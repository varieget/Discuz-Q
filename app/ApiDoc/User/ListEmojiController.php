<?php
/**
 *@OA\Get(
 *    path = "/apiv3/emoji",
 *    summary = "表情列表",
 *    description = "Discuz! Q 表情列表",
 *    tags ={"发布与展示"},
 *@OA\Parameter(ref = "#/components/parameters/bear_token"),
 *
 * @OA\Response(
 *        response = 200,
 *        description = "返回表情列表",
 *        @OA\JsonContent(allOf ={
 *                @OA\Schema(ref = "#/components/schemas/dzq_layout"),
 *                @OA\Schema(@OA\Property(property = "Data", type = "array", description="表情列表", @OA\Items(type = "object", ref = "#/components/schemas/dzq_emoji")))
 *            }
 *        )
 *    )
 *)
 *
 *
 */

