<?php

declare(strict_types=1);

namespace Tenqz\Qdrant\Transport\Infrastructure\Factory;

use Tenqz\Qdrant\Transport\Domain\Factory\HttpClientFactoryInterface;
use Tenqz\Qdrant\Transport\Domain\HttpClientInterface;
use Tenqz\Qdrant\Transport\Infrastructure\CurlHttpClient;

/**
 * Concrete factory for creating cURL-based HTTP client instances
 */
class CurlHttpClientFactory extends HttpClientFactoryInterface
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
    public function create(
        string $host = 'localhost',
        int $port = 6333,
        ?string $apiKey = null,
        int $timeout = 30,
        string $scheme = 'http'
    ): HttpClientInterface {
        $baseUrl = "{$scheme}://{$host}:{$port}";

        return new CurlHttpClient($baseUrl, $apiKey, $timeout);
    }
}
