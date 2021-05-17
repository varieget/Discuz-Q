<?php
/**
 * Copyright (C) 2021 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Api\Controller\WalletV3;


use App\Api\Serializer\UserWalletLogSerializer;
use App\Common\ResponseCode;
use App\Models\User;
use App\Models\UserWalletLog;
use App\Repositories\UserRepository;
use App\Repositories\UserWalletLogsRepository;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Base\DzqController;
use Discuz\Http\UrlGenerator;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tobscure\JsonApi\Parameters;

class ListUserWalletLogsController extends DzqController
{
    use AssertPermissionTrait;

    protected $bus;
    protected $url;
    protected $cash;
    protected $walletLogs;
    protected $sumChangeAvailableAmount;
    protected $walletLogType;

    public $include = [
        'user',
        'order',
        'order.user'
    ];

    public $optionalInclude = [
        'user',
        'userWallet',
        'sourceUser',
        'userWalletCash',
        'order.thread',
        'order.thread.user',
        'order.thread.firstPost',
    ];

    public $sort = [
        'created_at'    =>  'desc'
    ];

    public $sortFields = [
        'created_at',
        'updated_at'
    ];

    public $incomeLogChangeType = [
//        12, //提现解冻
        30, //注册收入
        31, //打赏收入
        32, //人工收入
        33, //分成打赏收入
        34, //注册分成收入
        35, //问答答题收入
        36, //问答围观收入
        60, //付费主题收入
        62, //分成付费主题收入
        63, //付费附件收入
        64, //付费附件分成收入
        102, //文字帖红包收入
        103, //文字帖冻结返还
        104, //文字帖订单异常返现
        112, //长文帖红包收入
        113, //长文帖冻结返还
        114, //长文帖订单异常返现
        120, //悬赏问答收入
        121, //悬赏帖过期-悬赏帖剩余悬赏金额返回
        122, //悬赏帖过期-悬赏帖剩余悬赏金额平分
        123, //悬赏帖过期-悬赏帖剩余悬赏金额按点赞数分配
        124, //问答帖订单异常返现
        151, //红包收入
        152, //红包退款
        154, //红包订单异常退款
        161, //悬赏问答收入
        162, //悬赏问答退款
        163, //悬赏订单异常退款
        171, //合并订单退款
        172, //合并订单异常退款
    ];

    public $expendLogChangeType = [
//        11, //提现成功
        41, //打赏支出
        50, //人工支出
        51, //加入用户组支出
        52, //付费附件支出
        61, //付费主题支出
        71, //站点续费支出
        81, //问答提问支出
        82, //问答围观支出
        100, //文字帖红包支出
        110, //长文帖红包支出
        153, //红包支出
    ];

    public $freezeLogChangeType = [
        8, //问答冻结
        9, //问答返还解冻
//        10, //提现冻结
        101, //文字帖红包冻结
        111, //长文帖红包冻结
        150, //红包冻结
        160, //悬赏问答冻结
        170, //合并订单冻结
    ];

    public function __construct(Dispatcher $bus, UrlGenerator $url, UserWalletLogsRepository $walletLogs)
    {
        $this->bus = $bus;
        $this->url = $url;
        $this->walletLogs = $walletLogs;
    }

    // 权限检查
    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        $actor = $this->user;
        if ($actor->isGuest()) {
            throw new PermissionDeniedException('没有权限');
        }
        return true;
    }


    public function main()
    {
        $user_wallet_log_serializer = $this->app->make(UserWalletLogSerializer::class);
        $user_wallet_log_serializer->setRequest($this->request);

        $this->walletLogType  = $this->inPut('walletLogType');
        $filter         = $this->inPut('filter') ?: [];
        $page           = $this->inPut('page') ?: 1;
        $perPage        = $this->inPut('perPage') ?: 5;
        $requestInclude = explode(',', $this->inPut('include'));

        if(!empty($this->inPut('include')) && is_array($requestInclude) && array_diff($requestInclude, $this->optionalInclude)){       //如果include 超出optionalinclude 就报错
            return $this->outPut(ResponseCode::NET_ERROR);
        }
        $sort           = (new Parameters($this->request->getQueryParams()))->getSort($this->sortFields) ?: $this->sort;
        $include        = !empty($requestInclude)
                            ? array_unique(array_merge($this->include, $requestInclude))
                            : $this->include;

        $walletLogs = $this->search($this->user, $filter, $sort, $page, $perPage);

        // 主题标题
        if (in_array('order.thread.firstPost', $include)) {
            $walletLogs['pageData']->load('order.thread.firstPost')
                ->map(function (UserWalletLog $log) {
                    if ($log->order && $log->order->thread) {
                        if ($log->order->thread->title) {
                            $title = Str::limit($log->order->thread->title, 40);
                        } else {
                            $title = Str::limit($log->order->thread->firstPost->content, 40);
                            $title = str_replace("\n", '', $title);
                        }

                        $log->order->thread->title = strip_tags($title);
                    }
                });
        }

        $data = $this->camelData($walletLogs);

        $data = $this->filterData($filter, $data);

        return $this->outPut(ResponseCode::SUCCESS,'', $data);
    }


    public function search($actor, $filter, $sort, $page = 0, $perPage = 0)
    {
        $query = $this->walletLogs->query()->whereVisibleTo($actor);
        $this->applyFilters($query, $filter, $actor);

        // 求和变动可用金额
        $this->sumChangeAvailableAmount = number_format($query->sum('change_available_amount'), 2);

        foreach ((array)$sort as $field => $order) {
            $query->orderBy(Str::snake($field), $order);
        }
        return $this->pagination($page, $perPage, $query, false);
    }


    private function applyFilters(Builder $query, array $filter, User $actor)
    {
        $log_user           = (int)Arr::get($filter, 'user'); //用户
        $log_change_desc    = Arr::get($filter, 'changeDesc'); //变动描述
        $log_change_type    = Arr::get($filter, 'changeType', ''); //变动类型
        $log_username       = Arr::get($filter, 'username'); //变动钱包所属人
        $log_start_time     = Arr::get($filter, 'startTime'); //变动时间范围：开始
        $log_end_time       = Arr::get($filter, 'endTime'); //变动时间范围：结束
        $log_source_user_id         = Arr::get($filter, 'sourceUserId');
        $log_change_type_exclude    = Arr::get($filter, 'changeTypeExclude');//排除变动类型

        $query->when($log_user, function ($query) use ($log_user) {
            $query->where('user_id', $log_user);
        });
        $query->when($log_change_desc, function ($query) use ($log_change_desc) {
            $query->where('change_desc', 'like', "%$log_change_desc%");
        });

        if (empty($log_change_type)) {
            if ($this->walletLogType == 'income') {
                $log_change_type = $this->incomeLogChangeType;
            } elseif ($this->walletLogType == 'expend') {
                $log_change_type = $this->expendLogChangeType;
            } elseif ($this->walletLogType == 'freeze') {
                $log_change_type = $this->freezeLogChangeType;
            }
        }
        if (!empty($log_change_type)) {
            $query->when($log_change_type, function ($query) use ($log_change_type) {
                $query->whereIn('change_type', $log_change_type);
            });
        }

        $query->when(!is_null($log_change_type_exclude), function ($query) use ($log_change_type_exclude) {
            $log_change_type_exclude = explode(',', $log_change_type_exclude);
            $query->whereNotIn('change_type', $log_change_type_exclude);
        });
        $query->when($log_start_time, function ($query) use ($log_start_time) {
            $query->where('created_at', '>=', $log_start_time);
        });
        $query->when($log_end_time, function ($query) use ($log_end_time) {
            $query->where('created_at', '<=', $log_end_time);
        });
        $query->when($log_username, function ($query) use ($log_username) {
            $query->whereIn('user_wallet_logs.user_id',
                            User::where('users.username', $log_username)
                                ->select('id', 'username')
                                ->get()
                            );
        });
        if (Arr::has($filter, 'source_username')) { // 有搜索 "0" 的情况
            $log_source_username = Arr::get($filter, 'source_username');
            $query->whereIn('user_wallet_logs.source_user_id',
                            User::query()
                                ->where('users.username', 'like', '%' . $log_source_username . '%')
                                ->pluck('id')
                            );
        }
        $query->when($log_source_user_id, function ($query) use ($log_source_user_id) {
            $query->where('source_user_id', '=', $log_source_user_id);
        });
    }

    public function filterData($filter, $data){
        if (empty(Arr::get($filter, 'changeType', ''))) {
            if ($this->walletLogType == 'income') {
                foreach ($data['pageData'] as $key => $val) {
                    $pageData = [
                        'id'            =>  $key,
                        'title'         =>  !empty($val['order']['thread']['title'])
                                                ? (!empty($val['order']['thread']['title']) ? $val['order']['thread']['title'] : '')
                                                : (!empty($val['order']['thread']['firstPost']['content']) ? $val['order']['thread']['firstPost']['content'] : ''),
                        'amount'        =>  !empty($val['order']['amount']) ? $val['order']['amount'] : 0,
                        'createdAt'     =>  !empty($val['createdAt']) ? $val['createdAt'] : 0,
                    ];
                    $data['pageData'][$key] =  $pageData;
                }
            } elseif ($this->walletLogType == 'expend') {
                foreach ($data['pageData'] as $key => $val) {
                    $pageData = [
                        'id'            =>  $key,
                        'title'         =>  !empty($val['order']['thread']['title'])
                                                ? (!empty($val['order']['thread']['title']) ? $val['order']['thread']['title'] : '')
                                                : (!empty($val['order']['thread']['firstPost']['content']) ? $val['order']['thread']['firstPost']['content'] : ''),
                        'amount'        =>  !empty($val['order']['amount']) ? $val['order']['amount'] : 0,
                        'createdAt'     =>  !empty($val['createdAt']) ? $val['createdAt'] : 0,
                        'status'        =>  !empty($val['order']['status']) ? $val['order']['status'] : '',
                    ];
                    $data['pageData'][$key] =  $pageData;
                }
            } elseif ($this->walletLogType == 'freeze') {
                foreach ($data['pageData'] as $key => $val) {
                    $pageData = [
                        'title'         =>  !empty($val['order']['thread']['title'])
                                                ? (!empty($val['order']['thread']['title']) ? $val['order']['thread']['title'] : '')
                                                : (!empty($val['order']['thread']['firstPost']['content']) ? $val['order']['thread']['firstPost']['content'] : ''),
                        'amount'        =>  !empty($val['order']['amount']) ? $val['order']['amount'] : 0,
                        'createdAt'     =>  !empty($val['createdAt']) ? $val['createdAt'] : 0,
                        'id'            =>  !empty($val['order']['status']) ? $val['order']['id'] : '',
                    ];
                    $data['pageData'][$key] =  $pageData;
                }
            }
        }

        return $data;
    }


}
