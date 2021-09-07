<?php
/**
 * @OA\Get(
 *     path="/apiv3/attachment.download",
 *     summary="附件生成url链接",
 *     description="附件生成url链接",
 *     tags={"附件"},
 *     @OA\Parameter(ref="#/components/parameters/bear_token"),
 *     @OA\Parameter(name="sign",
 *          in="query",
 *          required=true,
 *          description = "唯一识别",
 *          type="integer"),
 *     @OA\Parameter(name="attachmentsId",
 *          in="query",
 *          required=true,
 *          description = "附件id",
 *          type="integer"),
 * )
 */

