<?php
/**
 *
 * @OA\Get(
 *     path="/apiv3/thread.stick",
 *     summary="置顶列表",
 *     description="获取首页置顶贴列表",
 *     tags={"帖子列表"},
 *      @OA\Parameter(
 *         name="Authorization",
 *         in="header",
 *         required=true,
 *         description="Bearer {access-token}",
 *         @OA\Schema(
 *              type="string"
 *         )
 *      ),
 *     @OA\Parameter(
 *         name="nickname",
 *         in="query",
 *         required=true,
 *         description = "用户名称",
 *       @OA\Schema(
 *              type="string"
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="userId",
 *         in="query",
 *         required=true,
 *         description = "用户id",
 *        @OA\Schema(
 *              type="integer"
 *         )
 *     ),
 *      @OA\Response(
 *          response=200,
 *          description="返回置顶列表",
 *          @OA\JsonContent(type="object",
 *              @OA\Property(property="Code", type="integer"),
 *              @OA\Property(property="Message", type="string"),
 *              @OA\Property(property="Data", type="array",
 *                  @OA\Items(type="object",
 *                      @OA\Property(property="threadId", type="number",description="帖子id"),
 *                      @OA\Property(property="categoryId", type="number",description="分类id"),
 *                      @OA\Property(property="title", type="string",description="帖子标题"),
 *                      @OA\Property(property="updatedAt", type="string",description="更新时间"),
 *                      @OA\Property(property="canViewPosts", type="boolean",description="是否可查阅详情"),
 *                  ),
 *              ),
 *             @OA\Property(property="RequestId", type="string"),
 *             @OA\Property(property="RequestTime", type="string")
 *          )
 *       )
 * )
 *
 *
 */
