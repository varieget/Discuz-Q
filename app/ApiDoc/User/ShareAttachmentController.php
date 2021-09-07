<?php
/**
 * @OA\Get(
 *     path="/apiv3/attachment.share",
 *     summary="附件生成url链接",
 *     description="附件生成url链接",
 *     tags={"附件"},
 *     @OA\Parameter(ref="#/components/parameters/bear_token"),
 *     @OA\Parameter(name="threadId",
 *          in="query",
 *          required=true,
 *          description = "主题id",
 *          type="integer"),
 *     @OA\Parameter(name="attachmentsId",
 *          in="query",
 *          required=true,
 *          description = "附件id",
 *          type="integer"),
 *     @OA\Response(
 *          response=200,
 *          description="返回更新结果",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/dzq_layout"),
 *                  @OA\Schema(@OA\Property(property="Data",type="object",
 *                      @OA\Property(property="url",type="string",description = "下载链接")
 *                  ))
 *          }))
 * )
 */
