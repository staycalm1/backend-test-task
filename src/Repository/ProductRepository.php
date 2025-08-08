<?php

declare(strict_types = 1);

namespace Raketa\BackendTestTask\Repository;

use Doctrine\DBAL\Connection;
use Raketa\BackendTestTask\Repository\Entity\Product;

class ProductRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getByUuid(string $uuid): ?Product
    {
        $row = $this->connection->fetchAssociative("SELECT * FROM products WHERE uuid = ?", [$uuid]);
        return $row ?
            $this->make($row) :
            null;
    }

    /**
     * @return array<int, Product>
     * @throws \Doctrine\DBAL\Exception
     */
    public function getByCategory(string $category): array
    {
        return array_map(
            fn (array $row): Product => $this->make($row),
            $this->connection->fetchAllAssociative("SELECT * FROM products WHERE is_active = 1 AND category = ?", [$category])
        );
    }

    public function make(array $row): Product
    {
        return new Product(
            id: $row['id'],
            uuid: $row['uuid'],
            isActive: $row['is_active'],
            category: $row['category'],
            name: $row['name'],
            description: $row['description'],
            thumbnail: $row['thumbnail'],
            price: $row['price'],
        );
    }
}
