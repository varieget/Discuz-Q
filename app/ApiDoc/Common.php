<?php
/**
 * @OA\Server(url="https://discuz.chat",description="Discuz! Q 官方网站")
 * @OA\Server(url="https://www.techo.chat",description="Discuz! Q 体验站")
 *
 * @OA\Info(
 *     title="Discuz! Q后台接口文档",
 *     version="3.0",
 *     description="本文档适用于对Discuz! Q进行二开的用户参考使用",
 *     termsOfService="https://gitee.com/Discuz/Discuz-Q",
 *     @OA\Contact(email="coralchu@tencent.com"),
 *     @OA\License(name="Apache 2.0",url="https://discuz.com/docs")
 * )
 * @OA\Tag(
 *     name="发布与展示",
 *     description="帖子相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 * @OA\Tag(
 *     name="注册登录",
 *     description="登录注册相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 * @OA\Tag(
 *     name="个人中心",
 *     description="管理后台相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 * @OA\Tag(
 *     name="支付钱包",
 *     description="钱包相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 * @OA\Tag(
 *     name="管理后台",
 *     description="管理后台相关接口",
 *     @OA\ExternalDocumentation(
 *          description="Discuz! Q官方网站",
 *          url="http://discuz.chat"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="dzq_layout",
 *     title="接口返回",
 *     @OA\Property(property="Code",type="integer",description="dzq错误码"),
 *     @OA\Property(property="Message",type="string",description="错误描述信息"),
 *     @OA\Property(property="Data",description="api数据集",type="array",@OA\Items()),
 *     @OA\Property(property="RequestId",type="string",description="请求ID"),
 *     @OA\Property(property="RequestTime",format="datetime",default="2021-02-02 02:22:22", type="string",description="请求时间"),
 *     description="dzq接口的整体返回规范,Code等于0表示接口正常返回"
 * )
 * @OA\Schema(
 *     schema = "dzq_pagination",
 *     title = "分页接口模板",
 *     @OA\Property(property="pageData",type="array",description="分页数据",@OA\Items()),
 *     @OA\Property(property="currentPage",type="integer",format="number", default=1, description="当前页码"),
 *     @OA\Property(property="perPage",type="integer",format="number", default=20, description="每页数据条数"),
 *     @OA\Property(property="firstPageUrl",type="string",description="第一页数据地址"),
 *     @OA\Property(property="nextPageUrl",type="string",description="下一页数据地址"),
 *     @OA\Property(property="prePageUrl",type="string",description="上一页数据地址"),
 *     @OA\Property(property="pageLength",type="integer",description="页总数"),
 *     @OA\Property(property="totalCount",type="integer",description="全部数据条数"),
 *     @OA\Property(property="totalPage",type="integer",description="全部数据页数"),
 *     description="分页数据标准格式",
 * )
 * @OA\Parameter(
 *     parameter="bear_token",
 *     name="Authorization",
 *     in="header",
 *     required=true,
 *     description="Bearer Token",
 *     @OA\Schema(type="string")
 * )
 */
