<?php

declare(strict_types=1);

namespace Raketa\BackendTestTask\Repository;

use Psr\Log\LoggerInterface;
use Raketa\BackendTestTask\Domain\Cart;
use Raketa\BackendTestTask\Infrastructure\Connector;
use Raketa\BackendTestTask\Infrastructure\ConnectorFactory;
use Raketa\BackendTestTask\Infrastructure\ConnectorException;
use function session_id;

class CartManager
{
    public ?LoggerInterface $logger;
    private readonly Connector $connector;

    public function __construct(ConnectorFactory $connectorFactory)
    {
        $this->logger = null;
        $this->connector = $connectorFactory->forCart();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @throws \Raketa\BackendTestTask\Infrastructure\ConnectorException
     */
    public function saveCart(Cart $cart): bool
    {
        try {
            $sessionId = session_id();
            return $this->connector->set($sessionId, $cart);
        } catch (ConnectorException $e) {
            $this->logger?->error('SaveCartError', ['session_id' => $sessionId, 'exception' => $e->__toString()]);

            // Попробовать перейти на другой канал для корзины?
            // Иначе, остаётся только прокинуть ошибку дальше.
            throw $e;
        }
    }

    /**
     * @throws \Raketa\BackendTestTask\Infrastructure\ConnectorException
     */
    public function getCart(): ?Cart
    {
        try {
            $sessionId = session_id();
            return $this->connector->get($sessionId);
        } catch (ConnectorException $e) {
            $this->logger?->error('GetCartError', ['session_id' => $sessionId, 'exception' => $e->__toString()]);

            // Попробовать перейти на другой канал для корзины?
            // Иначе, остаётся только прокинуть ошибку дальше.
            throw $e;
        }
    }
}
