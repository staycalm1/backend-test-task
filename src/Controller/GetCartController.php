<?php

declare(strict_types=1);

namespace Raketa\BackendTestTask\Controller;

use Throwable;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Raketa\BackendTestTask\View\CartView;
use Raketa\BackendTestTask\Repository\CartManager;

readonly class GetCartController
{
    public function __construct(
        private CartView $cartView,
        private CartManager $cartManager,
        private LoggerInterface $logger,
    ) {
    }

    public function get(RequestInterface $request): ResponseInterface
    {
        try {
            $this->cartManager->setLogger($this->logger);

            $cart = $this->cartManager->getCart();

            if (!$cart) {
                return JsonResponse::fromArray(['message' => 'Cart not found'])
                    ->withStatus(404);
            }

            return JsonResponse::fromArray($this->cartView->toArray($cart))
                ->withStatus(200);
        } catch (Throwable $t) {
            $this->logger->error('GetCartControllerError', ['exception' => $t->__toString()]);

            return JsonResponse::fromArray(['status' => 'error', 'message' => 'Сервис временно недоступен'])
                ->withStatus(503);
        }
    }
}
