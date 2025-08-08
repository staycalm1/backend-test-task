<?php

namespace Raketa\BackendTestTask\Controller;

use Throwable;
use Ramsey\Uuid\Uuid;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Raketa\BackendTestTask\View\CartView;
use Raketa\BackendTestTask\Domain\CartItem;
use Raketa\BackendTestTask\Repository\CartManager;
use Raketa\BackendTestTask\Repository\ProductRepository;
use function json_decode;

readonly class AddToCartController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CartView $cartView,
        private CartManager $cartManager,
        private LoggerInterface $logger,
    ) {
    }

    public function get(RequestInterface $request): ResponseInterface
    {
        try {
            $rawRequest = json_decode($request->getBody()->getContents(), true);

            $product = $this->productRepository->getByUuid($rawRequest['productUuid']);
            if (!$product) {
                return JsonResponse::fromArray(['status' => 'error', 'message' => 'Не удалось обновить корзину'])
                    ->withStatus(422);
            }

            $this->cartManager->setLogger($this->logger);

            $cart = $this->cartManager->getCart();
            $cart->addItem(new CartItem(
                Uuid::uuid4()->toString(),
                $product->getUuid(),
                $product->getPrice(),
                $rawRequest['quantity'],
            ));

            if (!$this->cartManager->saveCart($cart)) {
                return JsonResponse::fromArray(['status' => 'error', 'message' => 'Сервис временно недоступен'])
                    ->withStatus(503);
            }

            return JsonResponse::fromArray(['status' => 'success', 'cart' => $this->cartView->toArray($cart)])
                ->withStatus(200);
        } catch (Throwable $t) {
            $this->logger->error('AddToCartControllerError', ['exception' => $t->__toString()]);

            return JsonResponse::fromArray(['status' => 'error', 'message' => 'Сервис временно недоступен'])
                ->withStatus(503);
        }
    }
}
