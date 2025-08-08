<?php

declare(strict_types=1);

namespace Raketa\BackendTestTask\Infrastructure;

use Redis;
use RedisException;
use Raketa\BackendTestTask\Domain\Cart;
use function serialize;
use function unserialize;

class Connector
{
    private ?Redis $redis;

    public function __construct(
        private readonly string $host,
        private readonly int $port = 6379,
        private readonly ?string $password = null,
        private readonly ?int $dbIndex = null,
    ) {
        $this->redis = null;
    }

    /**
     * @throws \Raketa\BackendTestTask\Infrastructure\ConnectorException
     */
    public function get(string $key): ?Cart
    {
        $valueRaw = $this->initAndTry(fn() => $this->redis->get($key));
        return $valueRaw ? unserialize($valueRaw) : null;
    }

    /**
     * @throws \Raketa\BackendTestTask\Infrastructure\ConnectorException
     */
    public function set(string $key, Cart $value): bool
    {
        return $this->initAndTry(fn() => $this->redis->setex($key, 24 * 60 * 60, serialize($value)));
    }

    /**
     * @throws \Raketa\BackendTestTask\Infrastructure\ConnectorException
     */
    public function has($key): bool
    {
        return $this->initAndTry(fn() => $this->redis->exists($key));
    }

    /**
     * @throws \Raketa\BackendTestTask\Infrastructure\ConnectorException
     */
    private function init(): void
    {
        if ($this->redis && $this->redis->isConnected()) {
            return;
        }

        $this->redis = new Redis();

        if (!$this->redis->pconnect(host: $this->host, port: $this->port, persistent_id: "for_cart:$this->dbIndex")) {
            throw new ConnectorException("Не удалось подключиться к Redis", 500, null);
        }

        if ($this->password && !$this->redis->auth($this->password)) {
            throw new ConnectorException("Не удалось авторизоваться в Redis", 500, null);
        }

        if ($this->dbIndex && !$this->redis->select($this->dbIndex)) {
            throw new ConnectorException("Не удалось выбрать БД в Redis", 500, null);
        }
    }

    /**
     * @throws \Raketa\BackendTestTask\Infrastructure\ConnectorException
     */
    private function initAndTry(callable $fn, int $maxRetry = 3): mixed
    {
        $lastException = null;

        while ($maxRetry--) {
            try {
                $this->init();
                return $fn();
            } catch (RedisException $e) {
                $lastException = new ConnectorException('Connector error', $e->getCode(), $e);
            } catch (ConnectorException $e) {
                $lastException = $e;
            }
        }

        throw $lastException;
    }
}
