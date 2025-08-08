<?php

declare(strict_types=1);

namespace Raketa\BackendTestTask\Infrastructure;

class ConnectorFactory
{
    private ?Connector $connectorForCart;

    public function __construct(
        private readonly string $host,
        private readonly int $port = 6379,
        private readonly ?string $password = null,
    ) {
        $this->connectorForCart = null;
    }

    public function forCart(): Connector
    {
        if (!$this->connectorForCart) {
            $this->connectorForCart = new Connector(host: $this->host, port: $this->port, password: $this->password, dbIndex: 1);
        }

        return $this->connectorForCart;
    }
}
