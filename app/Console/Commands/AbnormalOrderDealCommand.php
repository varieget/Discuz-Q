<?php

/**
 * Copyright (C) 2020 Tencent Cloud.
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

namespace App\Console\Commands;

use App\Commands\Order\RefundErrorThreadOrder;
use Discuz\Console\AbstractCommand;

class AbnormalOrderDealCommand extends AbstractCommand
{
    protected $signature = 'abnormalOrder:clear';

    protected $description = '处理问答提问支付、红包、悬赏的异常订单，返还金额给用户';

    public function handle()
    {
        /** @var RefundErrorThreadOrder $command */
        $command = app(RefundErrorThreadOrder::class);
        app()->call([$command, 'handle']);
    }
}
