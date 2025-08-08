<?php

declare(strict_types = 1);

namespace Raketa\BackendTestTask\View;

use Raketa\BackendTestTask\Domain\Cart;
use Raketa\BackendTestTask\Repository\ProductRepository;
use function bcadd;
use function bcmul;

readonly class CartView
{
    public function __construct(
        private ProductRepository $productRepository
    ) {
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function toArray(Cart $cart): array
    {
        $customer = $cart->getCustomer();

        $data = [
            'uuid' => $cart->getUuid(),
            'customer' => [
                'id' => $customer->getId(),
                'name' => implode(' ', array_filter([
                    $customer->getLastName(),
                    $customer->getFirstName(),
                    $customer->getMiddleName(),
                ])),
                'email' => $customer->getEmail(),
            ],
            'payment_method' => $cart->getPaymentMethod(),
        ];

        $total = 0;
        $data['items'] = [];
        foreach ($cart->getItems() as $item) {
            $subtotal = bcmul($item->getPrice(), $item->getQuantity(), 2);
            $total = bcadd($total, $subtotal, 2);

            $product = $this->productRepository->getByUuid($item->getProductUuid());
            $data['items'][] = [
                'uuid' => $item->getUuid(),
                'price' => $item->getPrice(),
                'total' => $subtotal,
                'quantity' => $item->getQuantity(),
                'product' => [
                    'id' => $product->getId(),
                    'uuid' => $product->getUuid(),
                    'name' => $product->getName(),
                    'thumbnail' => $product->getThumbnail(),
                    'price' => $product->getPrice(),
                ],
            ];
        }
        $data['total'] = $total;

        return $data;
    }
}
