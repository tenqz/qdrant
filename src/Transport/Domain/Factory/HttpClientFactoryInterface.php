<?php

declare(strict_types=1);

namespace Tenqz\Qdrant\Transport\Domain\Factory;

use Tenqz\Qdrant\Transport\Domain\HttpClientInterface;

/**
 * Abstract factory for creating HTTP client instances
 */
abstract class HttpClientFactoryInterface
{
    /**
     * Create HTTP client from connection parameters
     *
     * @param string $host Server host
     * @param int $port Server port
     * @param string|null $apiKey Optional API key for authentication
     * @param int $timeout Request timeout in seconds
     * @param string $scheme Protocol scheme (http or https)
     * @return HttpClientInterface
     */
    abstract public function create(
        string $host = 'localhost',
        int $port = 6333,
        ?string $apiKey = null,
        int $timeout = 30,
        string $scheme = 'http'
    ): HttpClientInterface;
}
