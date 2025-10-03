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

    // ========================================================================
    // Points API
    // ========================================================================

    /**
     * Upsert (insert or update) points into a collection
     *
     * Inserts new points or updates existing ones in the specified collection.
     * Each point must have an id and vector, and can optionally include payload data.
     *
     * @param string $collection Collection name
     * @param array $points Array of points with id, vector, and optional payload
     * @return array Response from Qdrant API
     * @throws TransportException On network or API errors
     *
     * Example:
     * $points = [
     *     ['id' => 1, 'vector' => [0.1, 0.2, 0.3], 'payload' => ['city' => 'Berlin']],
     *     ['id' => 2, 'vector' => [0.4, 0.5, 0.6], 'payload' => ['city' => 'Moscow']],
     * ];
     */
    public function upsertPoints(string $collection, array $points): array
    {
        return $this->request('PUT', "/collections/{$collection}/points", [
            'points' => $points,
        ]);
    }

    /**
     * Get a single point by ID
     *
     * Retrieves a specific point from the collection by its ID.
     * Returns the point's vector, payload, and metadata.
     *
     * @param string $collection Collection name
     * @param int|string $id Point ID
     * @return array Point data from Qdrant API
     * @throws TransportException On network or API errors
     */
    public function getPoint(string $collection, $id): array
    {
        return $this->request('GET', "/collections/{$collection}/points/{$id}");
    }

    /**
     * Get multiple points by IDs
     *
     * Retrieves multiple points from the collection by their IDs in a single request.
     * Allows selective inclusion of payload and vector data to optimize response size.
     *
     * @param string $collection Collection name
     * @param array $ids Array of point IDs (integers or strings)
     * @param bool $withPayload Include payload data in response (default: true)
     * @param bool $withVector Include vector data in response (default: false)
     * @return array Points data from Qdrant API
     * @throws TransportException On network or API errors
     *
     * Example:
     * $points = $client->getPoints('my_collection', [1, 2, 3], true, false);
     */
    public function getPoints(
        string $collection,
        array $ids,
        bool $withPayload = true,
        bool $withVector = false
    ): array {
        return $this->request('POST', "/collections/{$collection}/points", [
            'ids'          => $ids,
            'with_payload' => $withPayload,
            'with_vector'  => $withVector,
        ]);
    }

    /**
     * Delete points by IDs
     *
     * Permanently removes multiple points from the collection by their IDs.
     * This operation is atomic and cannot be undone.
     *
     * @param string $collection Collection name
     * @param array $ids Array of point IDs to delete (integers or strings)
     * @return array Response from Qdrant API with operation status
     * @throws TransportException On network or API errors
     *
     * Example:
     * $result = $client->deletePoints('my_collection', [1, 2, 3]);
     */
    public function deletePoints(string $collection, array $ids): array
    {
        return $this->request('POST', "/collections/{$collection}/points/delete", [
            'points' => $ids,
        ]);
    }

    /**
     * Update payload for specific points
     *
     * Sets or updates payload data for multiple points in the collection.
     * The payload is merged with existing data unless the point is new.
     *
     * @param string $collection Collection name
     * @param array $payload Payload data to set (key-value pairs)
     * @param array $points Array of point IDs to update
     * @return array Response from Qdrant API with operation status
     * @throws TransportException On network or API errors
     *
     * Example:
     * $result = $client->setPayload('my_collection', ['category' => 'tech'], [1, 2, 3]);
     */
    public function setPayload(string $collection, array $payload, array $points): array
    {
        return $this->request('POST', "/collections/{$collection}/points/payload", [
            'payload' => $payload,
            'points'  => $points,
        ]);
    }

    /**
     * Delete payload keys from points
     *
     * Removes specific payload fields from multiple points in the collection.
     * Only the specified keys are deleted, other payload data remains intact.
     *
     * @param string $collection Collection name
     * @param array $keys Array of payload keys to delete
     * @param array $points Array of point IDs to update
     * @return array Response from Qdrant API with operation status
     * @throws TransportException On network or API errors
     *
     * Example:
     * $result = $client->deletePayload('my_collection', ['old_field', 'temp_data'], [1, 2, 3]);
     */
    public function deletePayload(string $collection, array $keys, array $points): array
    {
        return $this->request('POST', "/collections/{$collection}/points/payload/delete", [
            'keys'   => $keys,
            'points' => $points,
        ]);
    }
}
