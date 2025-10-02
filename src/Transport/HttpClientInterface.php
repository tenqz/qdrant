<?php

declare(strict_types=1);

namespace Tenqz\Qdrant\Transport;

use Tenqz\Qdrant\Exception\QdrantException;

/**
 * HTTP client interface for API communication
 */
interface HttpClientInterface
{
    /**
     * Execute HTTP request
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $path API endpoint path
     * @param array|null $data Request body data
     * @return array Response data
     * @throws QdrantException
     */
    public function request(string $method, string $path, ?array $data = null): array;
}
