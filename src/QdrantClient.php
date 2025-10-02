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

    // ========================================================================
    // Collections API
    // ========================================================================

    /**
     * Create a new collection with vector configuration
     *
     * Creates a collection to store vectors with specified size and distance metric.
     * Optionally configure HNSW indexing and quantization for optimization.
     *
     * @param string $name Collection name
     * @param int $vectorSize Dimension of vectors (e.g., 128, 384, 768)
     * @param string $distance Distance metric: Cosine, Dot, Euclid, Manhattan
     * @param array|null $hnswConfig Optional HNSW index configuration
     * @param array|null $quantizationConfig Optional quantization settings
     * @return array Response from Qdrant API with collection status
     * @throws TransportException On network or API errors
     */
    public function createCollection(
        string $name,
        int $vectorSize,
        string $distance = 'Cosine',
        ?array $hnswConfig = null,
        ?array $quantizationConfig = null
    ): array {
        $config = [
            'vectors' => [
                'size'     => $vectorSize,
                'distance' => $distance,
            ],
        ];

        if ($hnswConfig !== null) {
            $config['hnsw_config'] = $hnswConfig;
        }

        if ($quantizationConfig !== null) {
            $config['quantization_config'] = $quantizationConfig;
        }

        return $this->request('PUT', "/collections/{$name}", $config);
    }

    /**
     * Get collection information
     *
     * Retrieves detailed information about a collection including configuration,
     * vector count, indexing status, and other metadata.
     *
     * @param string $name Collection name
     * @return array Collection information from Qdrant API
     * @throws TransportException On network or API errors
     */
    public function getCollection(string $name): array
    {
        return $this->request('GET', "/collections/{$name}");
    }

    /**
     * Delete a collection
     *
     * Permanently removes a collection and all its data from Qdrant.
     * This operation cannot be undone.
     *
     * @param string $name Collection name
     * @return array Response from Qdrant API
     * @throws TransportException On network or API errors
     */
    public function deleteCollection(string $name): array
    {
        return $this->request('DELETE', "/collections/{$name}");
    }

    /**
     * List all collections
     *
     * Retrieves a list of all collections in the Qdrant instance.
     *
     * @return array List of collections from Qdrant API
     * @throws TransportException On network or API errors
     */
    public function listCollections(): array
    {
        return $this->request('GET', '/collections');
    }
}
