<?php
/**
 * @OA\Post(
 *     path="/api/backAdmin/groups.create",
 *     summary="创建用户组",
 *     description="创建用户组",
 *     tags={"管理后台"},
 *     @OA\Parameter(ref="#/components/parameters/bear_token_true"),
 *     @OA\RequestBody(
 *         required=true,
 *         description = "参数",
 *         @OA\JsonContent(
 *            @OA\Property(property="name",type="string",description="名称"),
 *            @OA\Property(property="type",type="string",description="类型"),
 *            @OA\Property(property="default",type="string",description=""),
 *         )
 *     ),
 *     @OA\Response(response=200,description="返回用户组信息",
 *         @OA\JsonContent(allOf={
 *             @OA\Schema(ref="#/components/schemas/dzq_layout"),
 *             @OA\Schema(@OA\Property(property="Data",type="object",
 *                 @OA\Property(property="id",type="integer",description="用户组id"),
 *                 @OA\Property(property="name",type="string",description="用户组名称"),
 *                 @OA\Property(property="type",type="string",description="类型"),
 *                 @OA\Property(property="color",type="string",description="颜色"),
 *                 @OA\Property(property="icon",type="string",description="icon"),
 *                 @OA\Property(property="default",type="integer",description="是否默认"),
 *                 @OA\Property(property="isDisplay",type="integer",description="是否显示在用户名后"),
 *                 @OA\Property(property="isPaid",type="integer",description="是否收费：0不收费，1收费"),
 *                 @OA\Property(property="fee",type="string",description="收费金额"),
 *                 @OA\Property(property="days",type="string",description="付费获得天数"),
 *                 @OA\Property(property="scale",type="string",description="分成比例"),
 *                 @OA\Property(property="isSubordinate",type="integer",description="是否可以推广下线"),
 *                 @OA\Property(property="isCommission",type="integer",description="是否可以收入提成")
 *             ))
 *         })
 *     )
 * )
 */
