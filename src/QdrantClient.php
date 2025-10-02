<?php

declare(strict_types=1);

namespace Tenqz\Qdrant;

use Tenqz\Qdrant\Transport\Domain\Exception\TransportException;
use Tenqz\Qdrant\Transport\Domain\HttpClientInterface;

/**
 * Qdrant client for vector database operations
 */
class QdrantClient
{
    /** @var HttpClientInterface */
    private $httpClient;

    /**
     * @param HttpClientInterface $httpClient HTTP client instance
     */
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    // ========================================================================
    // Internal HTTP Methods
    // ========================================================================

    /**
     * Execute HTTP request to Qdrant API
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $path API endpoint path
     * @param array|null $data Request body data
     * @return array Response data
     * @throws TransportException
     */
    private function request(string $method, string $path, ?array $data = null): array
    {
        return $this->httpClient->request($method, $path, $data);
    }
}
