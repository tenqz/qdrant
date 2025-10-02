<?php

declare(strict_types=1);

namespace Tenqz\Qdrant;

/**
 * Simple Qdrant client for vector database operations
 *
 * Iteration 1: Basic implementation with cURL
 */
class QdrantClient
{
    /** @var string */
    private $baseUrl;

    /** @var string|null */
    private $apiKey;

    /** @var int */
    private $timeout;

    /**
     * @param string $host Qdrant server host
     * @param int $port Qdrant server port
     * @param string|null $apiKey Optional API key for authentication
     * @param int $timeout Request timeout in seconds
     */
    public function __construct(
        string $host = 'localhost',
        int $port = 6333,
        ?string $apiKey = null,
        int $timeout = 30
    ) {
        $this->baseUrl = "http://{$host}:{$port}";
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }
}
