<?php
/**
 *@OA\Get(
 *    path = "/apiv3/categories.thread",
 *    summary = "帖子分类列表",
 *    description = "Discuz! Q 帖子分类列表",
 *    tags ={"发布与展示"},
 *@OA\Parameter(ref = "#/components/parameters/bear_token"),
 *
 * @OA\Response(
 *        response = 200,
 *        description = "返回帖子分类列表",
 *        @OA\JsonContent(allOf ={
 *                @OA\Schema(ref = "#/components/schemas/dzq_layout"),
 *                @OA\Schema(@OA\Property(property = "Data", type = "array", description="帖子分类列表", @OA\Items(type = "object", ref = "#/components/schemas/dzq_category_thread")))
 *            }
 *        )
 *    )
 *)
 *
 *
 * @OA\Schema(
 *     schema = "dzq_category_thread",
 *     title = "分类详情",
 *     @OA\Property(property = "pid", type = "integer", description = "分类id"),
 *     @OA\Property(property = "canCreateThread", type = "boolean", description = "该分类是否能发帖子"),
 *     @OA\Property(property = "canEditThread", type = "boolean", description = "该分类是否能编辑帖子"),
 *     @OA\Property(property = "name", type = "string", description = "分类名称"),
 *     @OA\Property(property = "parentid", type = "integer", description = "父级分类id"),
 *     @OA\Property(property = "sort", type = "integer", description = "序号"),
 *     @OA\Property(property = "threadCount", type = "integer", description = "帖子数量"),
 *     @OA\Property(property = "children", type = "array", description = "子分类", @OA\Items(
 *     type="object",
 *          @OA\Property(property = "pid", type = "integer", description = "分类id"),
 *          @OA\Property(property = "canCreateThread", type = "boolean", description = "该分类是否能发帖"),
 *          @OA\Property(property = "canEditThread", type = "boolean", description = "该分类是否能编辑帖子"),
 *          @OA\Property(property = "name", type = "string", description = "分类名称"),
 *          @OA\Property(property = "parentid", type = "integer", description = "父级分类id"),
 *          @OA\Property(property = "sort", type = "integer", description = "序号"),
 *          @OA\Property(property = "threadCount", type = "integer", description = "帖子数量"),
 * ))
 * )
 */

