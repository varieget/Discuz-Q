<?php
/**
 * @OA\Get(
 *     path="/apiv3/tom.delete",
 *     summary="删除扩展对象里的指定索引下数据",
 *     description="例如扩展对象里包含两个视频对象，索引为$1和$2,该接口可定向删除其中一个视频对象【官方安装包暂未使用该接口】",
 *     tags={"发布与展示"},
 *     @OA\Parameter(ref="#/components/parameters/bear_token"),
 *     @OA\Parameter(name="threadId",in="query",required=true,description = "主题id",@OA\Schema(type="integer")),
 *     @OA\Parameter(name="tomId",in="query",required=true,description = "扩展对象id",@OA\Schema(type="integer")),
 *     @OA\Parameter(name="key",in="query",required=true,description = "对象所属索引标记",@OA\Schema(type="string")),
 *     @OA\Response(response=200,description="返回删除结果",@OA\JsonContent(ref="#/components/schemas/dzq_layout"))
 * )
 */
