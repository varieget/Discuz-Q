<?php
/**
 * @OA\Get(
 *     path="/api/backAdmin/plugin/settinginfo",
 *     summary="插件配置详情",
 *     description="查询当前插件的所有配置信息",
 *     tags={"插件"},
 *     @OA\Parameter(ref="#/components/parameters/bear_token"),
 *     @OA\Parameter(
 *         name="appId",
 *         in="query",
 *         required=true,
 *         description = "插件应用id",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="返回设置详情",
 *         @OA\JsonContent(allOf={
 *             @OA\Schema(ref = "#/components/schemas/dzq_layout"),
 *             @OA\Schema(@OA\Property(property = "Data", type = "array",@OA\Items(
 *                 @OA\Property(property="setting",type="object",description="插件面板的自定义JSON数据"),
 *                 @OA\Property(property="config",type="object",description="插件目录开发者定义的配置文件"),
 *             )))
 *         }))
 *     )
 * )
 */
