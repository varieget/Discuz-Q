<?php
/**
 * @OA\POST(
 *     path="/apiv3/thread.create",
 *     summary="发帖",
 *     description="统一发帖接口，所有帖子内容组件的数据合并一次性提交创建帖子",
 *     tags={"发布与展示"},
 *     @OA\Parameter(ref="#/components/parameters/bear_token"),
 * )
 */
