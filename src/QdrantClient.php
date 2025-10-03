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

    /**
     * Scroll through all points in a collection
     *
     * Retrieves points from the collection in batches with cursor-based pagination.
     * Useful for iterating through large collections without loading all data at once.
     *
     * @param string $collection Collection name
     * @param int $limit Maximum number of points to return per request (default: 100)
     * @param array|null $filter Optional filter conditions to apply
     * @param string|null $offset Pagination offset from previous scroll response
     * @param bool $withPayload Include payload data in response (default: true)
     * @param bool $withVector Include vector data in response (default: false)
     * @return array Response with points array and next_page_offset for pagination
     * @throws TransportException On network or API errors
     *
     * Example:
     * $result = $client->scroll('my_collection', 50);
     * $nextOffset = $result['result']['next_page_offset'] ?? null;
     */
    public function scroll(
        string $collection,
        int $limit = 100,
        ?array $filter = null,
        ?string $offset = null,
        bool $withPayload = true,
        bool $withVector = false
    ): array {
        $body = [
            'limit'        => $limit,
            'with_payload' => $withPayload,
            'with_vector'  => $withVector,
        ];

        if ($filter !== null) {
            $body['filter'] = $filter;
        }

        if ($offset !== null) {
            $body['offset'] = $offset;
        }

        return $this->request('POST', "/collections/{$collection}/points/scroll", $body);
    }

    /**
     * Count points in collection
     *
     * Returns the total number of points in the collection.
     * Optionally applies filter conditions to count only matching points.
     *
     * @param string $collection Collection name
     * @param array|null $filter Optional filter conditions to apply
     * @return array Count result from Qdrant API
     * @throws TransportException On network or API errors
     *
     * Example:
     * $result = $client->countPoints('my_collection');
     * $count = $result['result']['count'];
     */
    public function countPoints(string $collection, ?array $filter = null): array
    {
        $body = null;

        if ($filter !== null) {
            $body = ['filter' => $filter];
        }

        return $this->request('POST', "/collections/{$collection}/points/count", $body);
    }

    // ========================================================================
    // Search API
    // ========================================================================

    /**
     * Search for similar vectors
     *
     * Performs vector similarity search in the collection to find nearest neighbors.
     * Returns points ranked by their similarity to the query vector.
     *
     * @param string $collection Collection name
     * @param array $vector Query vector to search for similar vectors
     * @param int $limit Maximum number of results to return (default: 10)
     * @param array|null $filter Optional filter conditions to apply
     * @param bool $withPayload Include payload data in response (default: true)
     * @param bool $withVector Include vector data in response (default: false)
     * @param float|null $scoreThreshold Minimum similarity score threshold
     * @return array Search results with matched points and scores
     * @throws TransportException On network or API errors
     *
     * Example:
     * $results = $client->search('my_collection', [0.1, 0.2, 0.3], 10);
     * foreach ($results['result'] as $point) {
     *     echo "ID: {$point['id']}, Score: {$point['score']}\n";
     * }
     */
    public function search(
        string $collection,
        array $vector,
        int $limit = 10,
        ?array $filter = null,
        bool $withPayload = true,
        bool $withVector = false,
        ?float $scoreThreshold = null
    ): array {
        $body = [
            'vector'       => $vector,
            'limit'        => $limit,
            'with_payload' => $withPayload,
            'with_vector'  => $withVector,
        ];

        if ($filter !== null) {
            $body['filter'] = $filter;
        }

        if ($scoreThreshold !== null) {
            $body['score_threshold'] = $scoreThreshold;
        }

        return $this->request('POST', "/collections/{$collection}/points/search", $body);
    }

    /**
     * Recommend points based on positive and negative examples
     *
     * Finds similar points using positive examples (what you want) and negative examples
     * (what you don't want). The algorithm averages positive examples and moves away from negatives.
     *
     * @param string $collection Collection name
     * @param array $positive Array of positive example point IDs (what you like)
     * @param array $negative Array of negative example point IDs (what you don't like, default: [])
     * @param int $limit Maximum number of results to return (default: 10)
     * @param array|null $filter Optional filter conditions to apply
     * @param bool $withPayload Include payload data in response (default: true)
     * @param bool $withVector Include vector data in response (default: false)
     * @return array Recommendation results with matched points and scores
     * @throws TransportException On network or API errors
     *
     * Example:
     * $results = $client->recommend('my_collection', [1, 5, 10], [3, 7], 5);
     * foreach ($results['result'] as $point) {
     *     echo "Recommended ID: {$point['id']}, Score: {$point['score']}\n";
     * }
     */
    public function recommend(
        string $collection,
        array $positive,
        array $negative = [],
        int $limit = 10,
        ?array $filter = null,
        bool $withPayload = true,
        bool $withVector = false
    ): array {
        $body = [
            'positive'     => $positive,
            'negative'     => $negative,
            'limit'        => $limit,
            'with_payload' => $withPayload,
            'with_vector'  => $withVector,
        ];

        if ($filter !== null) {
            $body['filter'] = $filter;
        }

        return $this->request('POST', "/collections/{$collection}/points/recommend", $body);
    }

    /**
     * Search with multiple vectors (batch search)
     *
     * Performs multiple vector similarity searches in a single request for improved performance.
     * Each search can have its own vector, limit, filter, and other parameters.
     *
     * @param string $collection Collection name
     * @param array $searches Array of search request configurations
     * @return array Batch search results with multiple result sets
     * @throws TransportException On network or API errors
     *
     * Example:
     * $searches = [
     *     ['vector' => [0.1, 0.2, 0.3], 'limit' => 5],
     *     ['vector' => [0.4, 0.5, 0.6], 'limit' => 3, 'filter' => ['must' => [...]]],
     * ];
     * $results = $client->searchBatch('my_collection', $searches);
     * foreach ($results['result'] as $idx => $searchResult) {
     *     echo "Search {$idx} found " . count($searchResult) . " results\n";
     * }
     */
    public function searchBatch(string $collection, array $searches): array
    {
        return $this->request('POST', "/collections/{$collection}/points/search/batch", [
            'searches' => $searches,
        ]);
    }
}
