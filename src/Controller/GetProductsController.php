<?php

declare(strict_types = 1);

namespace Raketa\BackendTestTask\Controller;

use Throwable;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Raketa\BackendTestTask\View\ProductsView;
use function json_decode;

readonly class GetProductsController
{
    public function __construct(
        private ProductsView $productsVew,
        private LoggerInterface $logger,
    ) {
    }

    public function get(RequestInterface $request): ResponseInterface
    {
        try {
            $rawRequest = json_decode($request->getBody()->getContents(), true);

            return JsonResponse::fromArray($this->productsVew->toArray($rawRequest['category']))
                ->withStatus(200);
        } catch (Throwable $t) {
            $this->logger->error('GetProductsControllerError', ['exception' => $t->__toString()]);

            return JsonResponse::fromArray(['status' => 'error', 'message' => 'Сервис временно недоступен'])
                ->withStatus(503);
        }
    }
}
