<?php
/**
 * @OA\Info(title="获取置顶列表", version="3.0",description="获取置顶列表",
 *     @OA\Contact(url="http://discuz.chat,name="coralchu"),
 *     @OA\License(name="Apache 2.0")
 *     )
 */
/**
 * @OA\Server(url="https://discuz.chat/apiv3/thread.stick",description="hello")
 */

/**
 *
 * @OA\Get(
 *      path="/users",
 *      operationId="getListOfUsers",
 *      tags={"Users"},
 *      description="Get list of users",
 *      security={{"Authorization-Bearer":{}}},
 *      @OA\Parameter(
 *         name="Authorization",
 *         in="header",
 *         required=true,
 *         description="Bearer {access-token}",
 *         @OA\Schema(
 *              type="bearerAuth"
 *         )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Get list of users.",
 *          @OA\JsonContent(type="object",
 *              @OA\Property(property="message", type="string"),
 *              @OA\Property(property="data", type="array",
 *                  @OA\Items(type="object",
 *                      @OA\Property(property="id", type="integer",description="用户id"),
 *                      @OA\Property(property="name", type="string"),
 *                      @OA\Property(property="email", type="string"),
 *                  ),
 *              ),
 *          ),
 *       ),
 *       @OA\Response(response=401, description="Unauthorized"),
 *       @OA\Response(response=404, description="Not Found"),
 * )
 *
 *
 */
