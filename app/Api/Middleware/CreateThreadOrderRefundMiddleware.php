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

namespace App\Api\Middleware;

use App\Commands\Order\RefundErrorThreadOrder;
use App\Models\User;
use Discuz\Cache\CacheManager;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\Repository;

class CreateThreadOrderRefundMiddleware implements MiddlewareInterface
{
    protected $bus;

    protected $conn;

    protected $log;

    protected $cache;

    public function __construct(ConnectionInterface $connection, Dispatcher $bus, LoggerInterface $log, CacheManager $cache)
    {
        $this->conn = $connection;
        $this->bus = $bus;
        $this->log = $log;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var User $user */
        $user = $request->getAttribute('actor');
        if (
            $user->isGuest()
            || !$this->cache->add('create_thread_order_refund:' . $user->id, 'lock', 30)
        ) {
            return $handler->handle($request);
        }

        try {
            /** @var RefundErrorThreadOrder $command */
            $command = app(RefundErrorThreadOrder::class);
            $command->setUser($user);
            app()->call([$command, 'handle']);
        } catch (Exception $e) {
            $this->log->info('中间件处理发帖订单失败：' . $e->getMessage());
        }

        return $handler->handle($request);
    }
}
