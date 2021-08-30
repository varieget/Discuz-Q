<?php
/**
 * @OA\Post(
 *     path="/apiv3/thread.create",
 *     summary="发帖",
 *     description="统一发帖接口，所有帖子内容组件的数据合并一次性提交创建帖子",
 *     tags={"发布与展示"},
 *     @OA\Parameter(ref="#/components/parameters/bear_token"),
 *     @OA\RequestBody(
 *        required=true,
 *        description = "帖子原始内容",
 *        @OA\JsonContent(
 *           @OA\Property(property="title",type="string",description="帖子标题"),
 *           @OA\Property(property="categoryId",type="integer",description="分类id"),
 *           @OA\Property(property="price",type="number",description="付费贴价格"),
 *           @OA\Property(property="freeWords",type="number",description="免费字数百分比"),
 *           @OA\Property(property="attachmentPrice",type="number",description="附件价格"),
 *           @OA\Property(property="draft",type="integer",enum={0,1}, description="是否草稿"),
 *           @OA\Property(property="anonymous",type="integer",enum={0,1}, description="是否匿名"),
 *           @OA\Property(property="content",type="object",description="帖子正文"),
 *
 *
 *        )
 *     ),
 *     @OA\Response(
 *        response=200,
 *        description="返回帖子详情",
 *        @OA\JsonContent(ref = "#/components/schemas/dzq_thread")
 *     )
 * )
 */
