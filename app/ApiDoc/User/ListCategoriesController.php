<?php
/**
 *@OA\Get(
 *    path = "/api/v3/categories",
 *    summary = "分类列表",
 *    description = "Discuz! Q 分类列表",
 *    tags ={"发布与展示"},
 *@OA\Parameter(ref = "#/components/parameters/bear_token"),
 *
 * @OA\Response(
 *        response = 200,
 *        description = "返回分类列表",
 *        @OA\JsonContent(allOf ={
 *                @OA\Schema(ref = "#/components/schemas/dzq_layout"),
 *                @OA\Schema(title="分类列表", description="分类列表",@OA\Property(property = "Data", type = "array", description="分类列表", @OA\Items(type = "object",
 *                      @OA\Property(property = "categoryId", type = "integer", description = "分类id"),
 *                      @OA\Property(property = "canCreateThread", type = "boolean", description = "该分类是否能发帖"),
 *                      @OA\Property(property = "name", type = "string", description = "分类名称"),
 *                      @OA\Property(property = "parentid", type = "integer", description = "父级分类id"),
 *                      @OA\Property(property = "description", type = "string", description = "描述"),
 *                      @OA\Property(property = "icon", type = "string", description = "分类图标"),
 *                      @OA\Property(property = "property", type = "integer", description = "属性,0 正常 1 首页展示"),
 *                      @OA\Property(property = "sort", type = "integer", description = "序号"),
 *                      @OA\Property(property = "threadCount", type = "integer", description = "帖子数量"),
 *                      @OA\Property(property = "searchIds", type= "array",  description = "可搜索的子分类", @OA\Items(type="integer")),
 *                      @OA\Property(property = "children", type = "array", description = "子分类", @OA\Items(type="object",
 *                          @OA\Property(property = "categoryId", type = "integer", description = "分类id"),
 *                          @OA\Property(property = "canCreateThread", type = "boolean", description = "该分类是否能发帖"),
 *                          @OA\Property(property = "name", type = "string", description = "分类名称"),
 *                          @OA\Property(property = "parentid", type = "integer", description = "父级分类id"),
 *                          @OA\Property(property = "sort", type = "integer", description = "序号"),
 *                          @OA\Property(property = "threadCount", type = "integer", description = "帖子数量"))
 *                      )
 *                )))
 *            }
 *        )
 *    )
 *)
 *
 *
 */

