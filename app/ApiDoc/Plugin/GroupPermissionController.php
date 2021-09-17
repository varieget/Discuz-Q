<?php
/**
 * @OA\Post(
 *     path="/api/backAdmin/plugin/permission.switch",
 *     summary="插件权限控制",
 *     description="如果插件关联用户组权限，需要配置用户组下的插件权限开启或关闭",
 *     tags={"插件"},
 *     @OA\Parameter(ref="#/components/parameters/bear_token"),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="appId",type="string",description="插件应用id"),
 *             @OA\Property(property="groupId",type="integer",description="用户组id"),
 *             @OA\Property(property="type",type="integer",default=1,enum={0,1}, description="0：关闭插件权限，1：开启插件权限"),
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="返回插件操作结果",
 *         @OA\JsonContent(ref="#/components/schemas/dzq_layout")
 *     )
 * )
 */
