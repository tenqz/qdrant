<?php

declare(strict_types=1);

namespace Tenqz\Qdrant\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tenqz\Qdrant\QdrantClient;
use Tenqz\Qdrant\Transport\Domain\HttpClientInterface;

/**
 * Unit tests for QdrantClient
 */
class QdrantClientTest extends TestCase
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var QdrantClient */
    private $client;

    /**
     * Set up test dependencies before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->client = new QdrantClient($this->httpClient);
    }

    /**
     * Test that client can be instantiated with HTTP client
     *
     * @testdox Can instantiate client with HTTP client dependency
     */
    public function testCanInstantiateClient(): void
    {
        // Arrange - done in setUp()
        // Act - client created in setUp()

        // Assert
        $this->assertInstanceOf(QdrantClient::class, $this->client);
    }

    /**
     * Test that createCollection sends correct request to API
     *
     * @testdox Creates collection with basic vector configuration
     */
    public function testCreateCollectionWithBasicConfig(): void
    {
        // Arrange
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                '/collections/test_collection',
                [
                    'vectors' => [
                        'size'     => 128,
                        'distance' => 'Cosine',
                    ],
                ]
            )
            ->willReturn(['status' => 'ok']);

        // Act
        $result = $this->client->createCollection('test_collection', 128);

        // Assert
        $this->assertEquals(['status' => 'ok'], $result);
    }

    /**
     * Test that createCollection uses custom distance metric
     *
     * @testdox Creates collection with custom distance metric
     */
    public function testCreateCollectionWithCustomDistance(): void
    {
        // Arrange
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                '/collections/test_collection',
                [
                    'vectors' => [
                        'size'     => 256,
                        'distance' => 'Euclid',
                    ],
                ]
            )
            ->willReturn(['status' => 'ok']);

        // Act
        $result = $this->client->createCollection('test_collection', 256, 'Euclid');

        // Assert
        $this->assertEquals(['status' => 'ok'], $result);
    }

    /**
     * Test that createCollection includes HNSW configuration when provided
     *
     * @testdox Creates collection with HNSW index configuration
     */
    public function testCreateCollectionWithHnswConfig(): void
    {
        // Arrange
        $hnswConfig = [
            'm'                   => 16,
            'ef_construct'        => 100,
            'full_scan_threshold' => 10000,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                '/collections/test_collection',
                [
                    'vectors' => [
                        'size'     => 384,
                        'distance' => 'Cosine',
                    ],
                    'hnsw_config' => $hnswConfig,
                ]
            )
            ->willReturn(['status' => 'ok']);

        // Act
        $result = $this->client->createCollection('test_collection', 384, 'Cosine', $hnswConfig);

        // Assert
        $this->assertEquals(['status' => 'ok'], $result);
    }

    /**
     * Test that createCollection includes quantization configuration when provided
     *
     * @testdox Creates collection with quantization settings
     */
    public function testCreateCollectionWithQuantizationConfig(): void
    {
        // Arrange
        $quantizationConfig = [
            'scalar' => [
                'type'       => 'int8',
                'quantile'   => 0.99,
                'always_ram' => true,
            ],
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                '/collections/test_collection',
                [
                    'vectors' => [
                        'size'     => 768,
                        'distance' => 'Dot',
                    ],
                    'quantization_config' => $quantizationConfig,
                ]
            )
            ->willReturn(['status' => 'ok']);

        // Act
        $result = $this->client->createCollection('test_collection', 768, 'Dot', null, $quantizationConfig);

        // Assert
        $this->assertEquals(['status' => 'ok'], $result);
    }

    /**
     * Test that createCollection includes both HNSW and quantization configs
     *
     * @testdox Creates collection with both HNSW and quantization configurations
     */
    public function testCreateCollectionWithAllConfigs(): void
    {
        // Arrange
        $hnswConfig = [
            'm'            => 32,
            'ef_construct' => 200,
        ];

        $quantizationConfig = [
            'scalar' => [
                'type'     => 'int8',
                'quantile' => 0.95,
            ],
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                '/collections/production_vectors',
                [
                    'vectors' => [
                        'size'     => 512,
                        'distance' => 'Manhattan',
                    ],
                    'hnsw_config'         => $hnswConfig,
                    'quantization_config' => $quantizationConfig,
                ]
            )
            ->willReturn(['status' => 'ok', 'result' => true]);

        // Act
        $result = $this->client->createCollection(
            'production_vectors',
            512,
            'Manhattan',
            $hnswConfig,
            $quantizationConfig
        );

        // Assert
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('ok', $result['status']);
    }

    /**
     * Test that createCollection works with various vector sizes
     *
     * @testdox Creates collection with vector size $vectorSize
     * @dataProvider vectorSizeProvider
     */
    public function testCreateCollectionWithVariousVectorSizes(int $vectorSize): void
    {
        // Arrange
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                '/collections/test_collection',
                [
                    'vectors' => [
                        'size'     => $vectorSize,
                        'distance' => 'Cosine',
                    ],
                ]
            )
            ->willReturn(['status' => 'ok']);

        // Act
        $result = $this->client->createCollection('test_collection', $vectorSize);

        // Assert
        $this->assertEquals(['status' => 'ok'], $result);
    }

    /**
     * Data provider for testing various vector sizes
     * Covers common embedding dimensions used in ML models
     */
    public static function vectorSizeProvider(): array
    {
        return [
            'Small embeddings (128)'  => [128],
            'Medium embeddings (256)' => [256],
            'BERT base (384)'         => [384],
            'OpenAI ada-002 (512)'    => [512],
            'BERT large (768)'        => [768],
            'GPT embeddings (1024)'   => [1024],
            'Large embeddings (1536)' => [1536],
        ];
    }

    /**
     * Test that createCollection works with all distance metrics
     *
     * @testdox Creates collection with distance metric $distance
     * @dataProvider distanceMetricProvider
     */
    public function testCreateCollectionWithVariousDistanceMetrics(string $distance): void
    {
        // Arrange
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                '/collections/test_collection',
                [
                    'vectors' => [
                        'size'     => 128,
                        'distance' => $distance,
                    ],
                ]
            )
            ->willReturn(['status' => 'ok']);

        // Act
        $result = $this->client->createCollection('test_collection', 128, $distance);

        // Assert
        $this->assertEquals(['status' => 'ok'], $result);
    }

    /**
     * Data provider for testing all supported distance metrics
     */
    public static function distanceMetricProvider(): array
    {
        return [
            'Cosine similarity'  => ['Cosine'],
            'Dot product'        => ['Dot'],
            'Euclidean distance' => ['Euclid'],
            'Manhattan distance' => ['Manhattan'],
        ];
    }

    // ========================================================================
    // Get Collection Tests
    // ========================================================================

    /**
     * Test that getCollection retrieves collection information
     *
     * @testdox Retrieves collection information by name
     */
    public function testGetCollectionReturnsInfo(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'status'                => 'grey',
                'optimizer_status'      => 'ok',
                'indexed_vectors_count' => 0,
                'points_count'          => 0,
                'segments_count'        => 6,
                'config'                => [
                    'params' => [
                        'vectors' => [
                            'size'     => 768,
                            'distance' => 'Cosine',
                        ],
                        'shard_number'             => 1,
                        'replication_factor'       => 1,
                        'write_consistency_factor' => 1,
                        'on_disk_payload'          => true,
                    ],
                    'hnsw_config' => [
                        'm'                    => 16,
                        'ef_construct'         => 100,
                        'full_scan_threshold'  => 10000,
                        'max_indexing_threads' => 0,
                        'on_disk'              => false,
                    ],
                    'optimizer_config' => [
                        'deleted_threshold'        => 0.2,
                        'vacuum_min_vector_number' => 1000,
                        'default_segment_number'   => 0,
                        'max_segment_size'         => null,
                        'memmap_threshold'         => null,
                        'indexing_threshold'       => 20000,
                        'flush_interval_sec'       => 5,
                        'max_optimization_threads' => null,
                    ],
                    'wal_config' => [
                        'wal_capacity_mb'    => 32,
                        'wal_segments_ahead' => 0,
                        'wal_retain_closed'  => 1,
                    ],
                    'quantization_config' => null,
                    'strict_mode_config'  => [
                        'enabled' => false,
                    ],
                ],
                'payload_schema' => [],
            ],
            'status' => 'ok',
            'time'   => 0.000403577,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/collections/test_collection', null)
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getCollection('test_collection');

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that getCollection works with different collection names
     *
     * @testdox Retrieves collection "$collectionName"
     * @dataProvider collectionNameProvider
     */
    public function testGetCollectionWithVariousNames(string $collectionName): void
    {
        // Arrange
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', "/collections/{$collectionName}", null)
            ->willReturn(['result' => ['status' => 'green']]);

        // Act
        $result = $this->client->getCollection($collectionName);

        // Assert
        $this->assertArrayHasKey('result', $result);
    }

    /**
     * Data provider for testing various collection names
     */
    public static function collectionNameProvider(): array
    {
        return [
            'Simple name'      => ['my_collection'],
            'With numbers'     => ['collection_123'],
            'With underscores' => ['test_collection_v2'],
            'Production name'  => ['prod_vectors'],
            'Development name' => ['dev_embeddings'],
        ];
    }

    /**
     * Test that getCollection returns complete collection metadata with quantization
     *
     * @testdox Returns collection with quantization configuration
     */
    public function testGetCollectionWithQuantization(): void
    {
        // Arrange
        $fullResponse = [
            'result' => [
                'status'                => 'green',
                'optimizer_status'      => 'ok',
                'indexed_vectors_count' => 5000,
                'points_count'          => 5000,
                'segments_count'        => 10,
                'config'                => [
                    'params' => [
                        'vectors' => [
                            'size'     => 384,
                            'distance' => 'Dot',
                        ],
                        'shard_number'             => 2,
                        'replication_factor'       => 2,
                        'write_consistency_factor' => 1,
                        'on_disk_payload'          => false,
                    ],
                    'hnsw_config' => [
                        'm'                    => 32,
                        'ef_construct'         => 200,
                        'full_scan_threshold'  => 20000,
                        'max_indexing_threads' => 4,
                        'on_disk'              => true,
                    ],
                    'optimizer_config' => [
                        'deleted_threshold'        => 0.2,
                        'vacuum_min_vector_number' => 1000,
                        'default_segment_number'   => 5,
                        'max_segment_size'         => 200000,
                        'memmap_threshold'         => 50000,
                        'indexing_threshold'       => 20000,
                        'flush_interval_sec'       => 5,
                        'max_optimization_threads' => 2,
                    ],
                    'wal_config' => [
                        'wal_capacity_mb'    => 64,
                        'wal_segments_ahead' => 2,
                        'wal_retain_closed'  => 5,
                    ],
                    'quantization_config' => [
                        'scalar' => [
                            'type'       => 'int8',
                            'quantile'   => 0.99,
                            'always_ram' => true,
                        ],
                    ],
                    'strict_mode_config' => [
                        'enabled' => true,
                    ],
                ],
                'payload_schema' => [],
            ],
            'status' => 'ok',
            'time'   => 0.002145,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/collections/production_collection', null)
            ->willReturn($fullResponse);

        // Act
        $result = $this->client->getCollection('production_collection');

        // Assert
        $this->assertEquals($fullResponse, $result);
    }

    // ========================================================================
    // Delete Collection Tests
    // ========================================================================

    /**
     * Test that deleteCollection sends correct DELETE request
     *
     * @testdox Deletes collection successfully
     */
    public function testDeleteCollectionSuccessfully(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => true,
            'status' => 'ok',
            'time'   => 0.020391438,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('DELETE', '/collections/test_collection', null)
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deleteCollection('test_collection');

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deleteCollection works with different collection names
     *
     * @testdox Deletes collection "$collectionName"
     * @dataProvider collectionNameProvider
     */
    public function testDeleteCollectionWithVariousNames(string $collectionName): void
    {
        // Arrange
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('DELETE', "/collections/{$collectionName}", null)
            ->willReturn(['result' => true, 'status' => 'ok']);

        // Act
        $result = $this->client->deleteCollection($collectionName);

        // Assert
        $this->assertTrue($result['result']);
    }

    /**
     * Test that deleteCollection returns API response
     *
     * @testdox Returns complete response from Qdrant API after deletion
     */
    public function testDeleteCollectionReturnsCompleteResponse(): void
    {
        // Arrange
        $fullResponse = [
            'result' => true,
            'status' => 'ok',
            'time'   => 0.005678,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('DELETE', '/collections/my_vectors', null)
            ->willReturn($fullResponse);

        // Act
        $result = $this->client->deleteCollection('my_vectors');

        // Assert
        $this->assertEquals($fullResponse, $result);
    }

    // ========================================================================
    // List Collections Tests
    // ========================================================================

    /**
     * Test that listCollections retrieves all collections
     *
     * @testdox Lists all collections successfully
     */
    public function testListCollectionsSuccessfully(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'collections' => [
                    ['name' => 'test_collection'],
                    ['name' => 'prod_vectors'],
                    ['name' => 'dev_embeddings'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.000234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/collections', null)
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->listCollections();

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that listCollections returns empty list when no collections exist
     *
     * @testdox Returns empty list when no collections exist
     */
    public function testListCollectionsReturnsEmptyList(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'collections' => [],
            ],
            'status' => 'ok',
            'time'   => 0.000123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/collections', null)
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->listCollections();

        // Assert
        $this->assertEmpty($result['result']['collections']);
    }

    /**
     * Test that listCollections returns correct structure
     *
     * @testdox Returns collections with correct structure
     */
    public function testListCollectionsReturnsCorrectStructure(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'collections' => [
                    ['name' => 'collection_one'],
                    ['name' => 'collection_two'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.000456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/collections', null)
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->listCollections();

        // Assert
        $this->assertCount(2, $result['result']['collections']);
    }

    // ========================================================================
    // Upsert Points Tests
    // ========================================================================

    /**
     * Test that upsertPoints inserts points successfully
     *
     * @testdox Upserts points into collection successfully
     */
    public function testUpsertPointsSuccessfully(): void
    {
        // Arrange
        $points = [
            ['id' => 1, 'vector' => [0.1, 0.2, 0.3], 'payload' => ['city' => 'Berlin']],
            ['id' => 2, 'vector' => [0.4, 0.5, 0.6], 'payload' => ['city' => 'Moscow']],
        ];

        $expectedResponse = [
            'result' => [
                'operation_id' => 0,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.001234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('PUT', '/collections/test_collection/points', ['points' => $points])
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->upsertPoints('test_collection', $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that upsertPoints works with single point
     *
     * @testdox Upserts single point successfully
     */
    public function testUpsertSinglePoint(): void
    {
        // Arrange
        $points = [
            ['id' => 1, 'vector' => [0.1, 0.2, 0.3, 0.4]],
        ];

        $expectedResponse = [
            'result' => [
                'operation_id' => 1,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.000567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('PUT', '/collections/my_vectors/points', ['points' => $points])
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->upsertPoints('my_vectors', $points);

        // Assert
        $this->assertEquals('completed', $result['result']['status']);
    }

    /**
     * Test that upsertPoints works without payload
     *
     * @testdox Upserts points without payload data
     */
    public function testUpsertPointsWithoutPayload(): void
    {
        // Arrange
        $points = [
            ['id' => 1, 'vector' => [0.1, 0.2, 0.3]],
            ['id' => 2, 'vector' => [0.4, 0.5, 0.6]],
            ['id' => 3, 'vector' => [0.7, 0.8, 0.9]],
        ];

        $expectedResponse = [
            'result' => [
                'operation_id' => 2,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.002345,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('PUT', '/collections/vectors/points', ['points' => $points])
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->upsertPoints('vectors', $points);

        // Assert
        $this->assertCount(3, $points);
    }

    // ========================================================================
    // Get Point Tests
    // ========================================================================

    /**
     * Test that getPoint retrieves point by integer ID
     *
     * @testdox Gets point by integer ID successfully
     */
    public function testGetPointByIntegerId(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'id'      => 1,
                'version' => 0,
                'vector'  => [0.1, 0.2, 0.3, 0.4],
                'payload' => ['city' => 'Berlin', 'category' => 'A'],
            ],
            'status' => 'ok',
            'time'   => 0.000123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/collections/test_collection/points/1', null)
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getPoint('test_collection', 1);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that getPoint retrieves point by string ID
     *
     * @testdox Gets point by string ID successfully
     */
    public function testGetPointByStringId(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'id'      => 'uuid-123-456',
                'version' => 5,
                'vector'  => [0.5, 0.6, 0.7],
                'payload' => ['name' => 'Document A'],
            ],
            'status' => 'ok',
            'time'   => 0.000234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/collections/my_vectors/points/uuid-123-456', null)
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getPoint('my_vectors', 'uuid-123-456');

        // Assert
        $this->assertEquals('uuid-123-456', $result['result']['id']);
    }

    /**
     * Test that getPoint returns complete point data
     *
     * @testdox Returns complete point data with all fields
     */
    public function testGetPointReturnsCompleteData(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'id'      => 42,
                'version' => 10,
                'vector'  => [0.1, 0.2, 0.3, 0.4, 0.5],
                'payload' => [
                    'title'      => 'Sample Document',
                    'category'   => 'Technology',
                    'tags'       => ['ai', 'ml', 'vectors'],
                    'created_at' => '2024-01-01',
                ],
            ],
            'status' => 'ok',
            'time'   => 0.000567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', '/collections/documents/points/42', null)
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getPoint('documents', 42);

        // Assert
        $this->assertArrayHasKey('payload', $result['result']);
    }

    // ========================================================================
    // Get Multiple Points Tests
    // ========================================================================

    /**
     * Test that getPoints retrieves multiple points with default parameters
     *
     * @testdox Gets multiple points with payload included and vector excluded by default
     */
    public function testGetMultiplePointsWithDefaultParameters(): void
    {
        // Arrange
        $ids = [1, 2, 3];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 1,
                    'version' => 0,
                    'payload' => ['city' => 'Berlin'],
                ],
                [
                    'id'      => 2,
                    'version' => 0,
                    'payload' => ['city' => 'Moscow'],
                ],
                [
                    'id'      => 3,
                    'version' => 0,
                    'payload' => ['city' => 'London'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.001234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points',
                [
                    'ids'          => $ids,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getPoints('test_collection', $ids);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that getPoints retrieves multiple points with vectors included
     *
     * @testdox Gets multiple points with vectors when withVector is true
     */
    public function testGetMultiplePointsWithVectors(): void
    {
        // Arrange
        $ids = [10, 20];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 10,
                    'version' => 5,
                    'vector'  => [0.1, 0.2, 0.3, 0.4],
                    'payload' => ['category' => 'A'],
                ],
                [
                    'id'      => 20,
                    'version' => 3,
                    'vector'  => [0.5, 0.6, 0.7, 0.8],
                    'payload' => ['category' => 'B'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.002345,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/my_vectors/points',
                [
                    'ids'          => $ids,
                    'with_payload' => true,
                    'with_vector'  => true,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getPoints('my_vectors', $ids, true, true);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that getPoints retrieves multiple points without payload
     *
     * @testdox Gets multiple points without payload when withPayload is false
     */
    public function testGetMultiplePointsWithoutPayload(): void
    {
        // Arrange
        $ids = [100, 200, 300];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 100,
                    'version' => 1,
                ],
                [
                    'id'      => 200,
                    'version' => 2,
                ],
                [
                    'id'      => 300,
                    'version' => 3,
                ],
            ],
            'status' => 'ok',
            'time'   => 0.000987,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points',
                [
                    'ids'          => $ids,
                    'with_payload' => false,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getPoints('vectors', $ids, false, false);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that getPoints retrieves multiple points with only vectors (no payload)
     *
     * @testdox Gets multiple points with vectors only when payload is excluded
     */
    public function testGetMultiplePointsWithVectorsOnly(): void
    {
        // Arrange
        $ids = [5, 10, 15];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 5,
                    'version' => 0,
                    'vector'  => [0.1, 0.2, 0.3],
                ],
                [
                    'id'      => 10,
                    'version' => 0,
                    'vector'  => [0.4, 0.5, 0.6],
                ],
                [
                    'id'      => 15,
                    'version' => 0,
                    'vector'  => [0.7, 0.8, 0.9],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.001567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/embeddings/points',
                [
                    'ids'          => $ids,
                    'with_payload' => false,
                    'with_vector'  => true,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getPoints('embeddings', $ids, false, true);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that getPoints works with string IDs
     *
     * @testdox Gets multiple points using string IDs (UUIDs)
     */
    public function testGetMultiplePointsWithStringIds(): void
    {
        // Arrange
        $ids = ['uuid-123', 'uuid-456', 'uuid-789'];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 'uuid-123',
                    'version' => 2,
                    'payload' => ['name' => 'Document A'],
                ],
                [
                    'id'      => 'uuid-456',
                    'version' => 4,
                    'payload' => ['name' => 'Document B'],
                ],
                [
                    'id'      => 'uuid-789',
                    'version' => 1,
                    'payload' => ['name' => 'Document C'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.002123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/documents/points',
                [
                    'ids'          => $ids,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getPoints('documents', $ids);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that getPoints works with mixed integer and string IDs
     *
     * @testdox Gets multiple points with mixed ID types
     */
    public function testGetMultiplePointsWithMixedIds(): void
    {
        // Arrange
        $ids = [1, 'uuid-456', 999];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 1,
                    'version' => 0,
                    'payload' => ['type' => 'numeric'],
                ],
                [
                    'id'      => 'uuid-456',
                    'version' => 5,
                    'payload' => ['type' => 'string'],
                ],
                [
                    'id'      => 999,
                    'version' => 2,
                    'payload' => ['type' => 'numeric'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.001789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/mixed_collection/points',
                [
                    'ids'          => $ids,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getPoints('mixed_collection', $ids);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that getPoints works with single ID in array
     *
     * @testdox Gets points when only one ID is provided in array
     */
    public function testGetMultiplePointsWithSingleId(): void
    {
        // Arrange
        $ids = [42];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 42,
                    'version' => 10,
                    'payload' => ['status' => 'active'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.000456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points',
                [
                    'ids'          => $ids,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getPoints('test_collection', $ids);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that getPoints returns complete response with all metadata
     *
     * @testdox Returns complete API response with status and timing information
     */
    public function testGetMultiplePointsReturnsCompleteResponse(): void
    {
        // Arrange
        $ids = [1, 2];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 1,
                    'version' => 3,
                    'vector'  => [0.1, 0.2, 0.3, 0.4, 0.5],
                    'payload' => [
                        'title'      => 'First Document',
                        'category'   => 'Tech',
                        'tags'       => ['ai', 'ml'],
                        'created_at' => '2024-01-01',
                    ],
                ],
                [
                    'id'      => 2,
                    'version' => 7,
                    'vector'  => [0.6, 0.7, 0.8, 0.9, 1.0],
                    'payload' => [
                        'title'      => 'Second Document',
                        'category'   => 'Science',
                        'tags'       => ['physics', 'research'],
                        'created_at' => '2024-01-02',
                    ],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.003456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/full_collection/points',
                [
                    'ids'          => $ids,
                    'with_payload' => true,
                    'with_vector'  => true,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->getPoints('full_collection', $ids, true, true);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    // ========================================================================
    // Delete Multiple Points Tests
    // ========================================================================

    /**
     * Test that deletePoints deletes multiple points successfully
     *
     * @testdox Deletes multiple points by IDs successfully
     */
    public function testDeleteMultiplePointsSuccessfully(): void
    {
        // Arrange
        $ids = [1, 2, 3];
        $expectedResponse = [
            'result' => [
                'operation_id' => 0,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.002345,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/delete',
                ['points' => $ids]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePoints('test_collection', $ids);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePoints works with string IDs
     *
     * @testdox Deletes multiple points using string IDs (UUIDs)
     */
    public function testDeleteMultiplePointsWithStringIds(): void
    {
        // Arrange
        $ids = ['uuid-123', 'uuid-456', 'uuid-789'];
        $expectedResponse = [
            'result' => [
                'operation_id' => 1,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.003456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/documents/points/delete',
                ['points' => $ids]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePoints('documents', $ids);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePoints works with mixed ID types
     *
     * @testdox Deletes multiple points with mixed integer and string IDs
     */
    public function testDeleteMultiplePointsWithMixedIds(): void
    {
        // Arrange
        $ids = [1, 'uuid-456', 999];
        $expectedResponse = [
            'result' => [
                'operation_id' => 2,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.001789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/mixed_collection/points/delete',
                ['points' => $ids]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePoints('mixed_collection', $ids);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePoints works with single ID
     *
     * @testdox Deletes single point when only one ID is provided
     */
    public function testDeleteMultiplePointsWithSingleId(): void
    {
        // Arrange
        $ids = [42];
        $expectedResponse = [
            'result' => [
                'operation_id' => 3,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.000987,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/delete',
                ['points' => $ids]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePoints('test_collection', $ids);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePoints works with large batch of IDs
     *
     * @testdox Deletes large batch of points successfully
     */
    public function testDeleteMultiplePointsWithLargeBatch(): void
    {
        // Arrange
        $ids = range(1, 100);
        $expectedResponse = [
            'result' => [
                'operation_id' => 4,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.015678,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/large_collection/points/delete',
                ['points' => $ids]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePoints('large_collection', $ids);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePoints returns operation status
     *
     * @testdox Returns operation status after deletion
     */
    public function testDeleteMultiplePointsReturnsOperationStatus(): void
    {
        // Arrange
        $ids = [10, 20, 30];
        $expectedResponse = [
            'result' => [
                'operation_id' => 5,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.002456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/delete',
                ['points' => $ids]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePoints('vectors', $ids);

        // Assert
        $this->assertEquals('completed', $result['result']['status']);
    }

    /**
     * Test that deletePoints works with different collection names
     *
     * @testdox Deletes points from collection with specific name
     */
    public function testDeleteMultiplePointsFromSpecificCollection(): void
    {
        // Arrange
        $ids = [1, 2];
        $expectedResponse = [
            'result' => [
                'operation_id' => 6,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.001234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/production_vectors/points/delete',
                ['points' => $ids]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePoints('production_vectors', $ids);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePoints returns complete API response
     *
     * @testdox Returns complete response with operation metadata
     */
    public function testDeleteMultiplePointsReturnsCompleteResponse(): void
    {
        // Arrange
        $ids = [100, 200, 300];
        $expectedResponse = [
            'result' => [
                'operation_id' => 7,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.004567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/my_collection/points/delete',
                ['points' => $ids]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePoints('my_collection', $ids);

        // Assert
        $this->assertArrayHasKey('result', $result);
    }

    // ========================================================================
    // Set Payload Tests
    // ========================================================================

    /**
     * Test that setPayload updates payload for multiple points successfully
     *
     * @testdox Sets payload for multiple points successfully
     */
    public function testSetPayloadForMultiplePointsSuccessfully(): void
    {
        // Arrange
        $payload = ['category' => 'tech', 'status' => 'active'];
        $points = [1, 2, 3];
        $expectedResponse = [
            'result' => [
                'operation_id' => 0,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.002345,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/payload',
                [
                    'payload' => $payload,
                    'points'  => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->setPayload('test_collection', $payload, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that setPayload works with string IDs
     *
     * @testdox Sets payload for points with string IDs (UUIDs)
     */
    public function testSetPayloadWithStringIds(): void
    {
        // Arrange
        $payload = ['name' => 'Document', 'type' => 'article'];
        $points = ['uuid-123', 'uuid-456'];
        $expectedResponse = [
            'result' => [
                'operation_id' => 1,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.001789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/documents/points/payload',
                [
                    'payload' => $payload,
                    'points'  => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->setPayload('documents', $payload, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that setPayload works with mixed ID types
     *
     * @testdox Sets payload for points with mixed integer and string IDs
     */
    public function testSetPayloadWithMixedIds(): void
    {
        // Arrange
        $payload = ['updated' => true];
        $points = [1, 'uuid-456', 999];
        $expectedResponse = [
            'result' => [
                'operation_id' => 2,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.002456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/mixed_collection/points/payload',
                [
                    'payload' => $payload,
                    'points'  => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->setPayload('mixed_collection', $payload, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that setPayload works with single point
     *
     * @testdox Sets payload for single point
     */
    public function testSetPayloadForSinglePoint(): void
    {
        // Arrange
        $payload = ['status' => 'processed', 'score' => 0.95];
        $points = [42];
        $expectedResponse = [
            'result' => [
                'operation_id' => 3,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.000987,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/payload',
                [
                    'payload' => $payload,
                    'points'  => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->setPayload('test_collection', $payload, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that setPayload works with complex nested payload
     *
     * @testdox Sets complex nested payload successfully
     */
    public function testSetPayloadWithComplexNestedData(): void
    {
        // Arrange
        $payload = [
            'title'    => 'Advanced Document',
            'metadata' => [
                'author'  => 'John Doe',
                'version' => 2,
                'tags'    => ['ai', 'ml', 'nlp'],
            ],
            'stats' => [
                'views'     => 1500,
                'downloads' => 320,
            ],
        ];
        $points = [10, 20];
        $expectedResponse = [
            'result' => [
                'operation_id' => 4,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.003456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/documents/points/payload',
                [
                    'payload' => $payload,
                    'points'  => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->setPayload('documents', $payload, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that setPayload works with empty payload
     *
     * @testdox Sets empty payload successfully
     */
    public function testSetPayloadWithEmptyPayload(): void
    {
        // Arrange
        $payload = [];
        $points = [1, 2];
        $expectedResponse = [
            'result' => [
                'operation_id' => 5,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.001234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/payload',
                [
                    'payload' => $payload,
                    'points'  => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->setPayload('test_collection', $payload, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that setPayload works with boolean values in payload
     *
     * @testdox Sets payload with boolean values
     */
    public function testSetPayloadWithBooleanValues(): void
    {
        // Arrange
        $payload = [
            'is_active'   => true,
            'is_verified' => false,
            'is_premium'  => true,
        ];
        $points = [5, 10];
        $expectedResponse = [
            'result' => [
                'operation_id' => 6,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.001567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/users/points/payload',
                [
                    'payload' => $payload,
                    'points'  => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->setPayload('users', $payload, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that setPayload works with numeric values in payload
     *
     * @testdox Sets payload with numeric values
     */
    public function testSetPayloadWithNumericValues(): void
    {
        // Arrange
        $payload = [
            'price'  => 29.99,
            'rating' => 4.5,
            'count'  => 150,
        ];
        $points = [100, 200];
        $expectedResponse = [
            'result' => [
                'operation_id' => 7,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.002123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/products/points/payload',
                [
                    'payload' => $payload,
                    'points'  => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->setPayload('products', $payload, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that setPayload returns operation status
     *
     * @testdox Returns operation status after setting payload
     */
    public function testSetPayloadReturnsOperationStatus(): void
    {
        // Arrange
        $payload = ['updated_at' => '2024-01-01'];
        $points = [1, 2, 3];
        $expectedResponse = [
            'result' => [
                'operation_id' => 8,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.002789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/payload',
                [
                    'payload' => $payload,
                    'points'  => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->setPayload('vectors', $payload, $points);

        // Assert
        $this->assertEquals('completed', $result['result']['status']);
    }

    /**
     * Test that setPayload returns complete API response
     *
     * @testdox Returns complete response with all metadata
     */
    public function testSetPayloadReturnsCompleteResponse(): void
    {
        // Arrange
        $payload = ['field' => 'value'];
        $points = [1];
        $expectedResponse = [
            'result' => [
                'operation_id' => 9,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.001456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/my_collection/points/payload',
                [
                    'payload' => $payload,
                    'points'  => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->setPayload('my_collection', $payload, $points);

        // Assert
        $this->assertArrayHasKey('result', $result);
    }

    // ========================================================================
    // Delete Payload Tests
    // ========================================================================

    /**
     * Test that deletePayload removes payload keys successfully
     *
     * @testdox Deletes payload keys from multiple points successfully
     */
    public function testDeletePayloadFromMultiplePointsSuccessfully(): void
    {
        // Arrange
        $keys = ['old_field', 'temp_data'];
        $points = [1, 2, 3];
        $expectedResponse = [
            'result' => [
                'operation_id' => 0,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.002345,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/payload/delete',
                [
                    'keys'   => $keys,
                    'points' => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePayload('test_collection', $keys, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePayload works with string IDs
     *
     * @testdox Deletes payload keys from points with string IDs (UUIDs)
     */
    public function testDeletePayloadWithStringIds(): void
    {
        // Arrange
        $keys = ['category', 'tags'];
        $points = ['uuid-123', 'uuid-456'];
        $expectedResponse = [
            'result' => [
                'operation_id' => 1,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.001789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/documents/points/payload/delete',
                [
                    'keys'   => $keys,
                    'points' => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePayload('documents', $keys, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePayload works with mixed ID types
     *
     * @testdox Deletes payload keys from points with mixed integer and string IDs
     */
    public function testDeletePayloadWithMixedIds(): void
    {
        // Arrange
        $keys = ['temp'];
        $points = [1, 'uuid-456', 999];
        $expectedResponse = [
            'result' => [
                'operation_id' => 2,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.002456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/mixed_collection/points/payload/delete',
                [
                    'keys'   => $keys,
                    'points' => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePayload('mixed_collection', $keys, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePayload works with single point
     *
     * @testdox Deletes payload keys from single point
     */
    public function testDeletePayloadFromSinglePoint(): void
    {
        // Arrange
        $keys = ['draft', 'preview'];
        $points = [42];
        $expectedResponse = [
            'result' => [
                'operation_id' => 3,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.000987,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/payload/delete',
                [
                    'keys'   => $keys,
                    'points' => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePayload('test_collection', $keys, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePayload works with single key
     *
     * @testdox Deletes single payload key from multiple points
     */
    public function testDeletePayloadWithSingleKey(): void
    {
        // Arrange
        $keys = ['deprecated_field'];
        $points = [1, 2, 3, 4, 5];
        $expectedResponse = [
            'result' => [
                'operation_id' => 4,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.003456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/payload/delete',
                [
                    'keys'   => $keys,
                    'points' => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePayload('vectors', $keys, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePayload works with multiple keys
     *
     * @testdox Deletes multiple payload keys from points
     */
    public function testDeletePayloadWithMultipleKeys(): void
    {
        // Arrange
        $keys = ['temp_field1', 'temp_field2', 'old_data', 'cache'];
        $points = [10, 20];
        $expectedResponse = [
            'result' => [
                'operation_id' => 5,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.002123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/cleanup/points/payload/delete',
                [
                    'keys'   => $keys,
                    'points' => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePayload('cleanup', $keys, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePayload works with nested key paths
     *
     * @testdox Deletes nested payload keys using dot notation
     */
    public function testDeletePayloadWithNestedKeys(): void
    {
        // Arrange
        $keys = ['metadata.temp', 'stats.cache'];
        $points = [1, 2];
        $expectedResponse = [
            'result' => [
                'operation_id' => 6,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.001567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/documents/points/payload/delete',
                [
                    'keys'   => $keys,
                    'points' => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePayload('documents', $keys, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePayload works with large batch of points
     *
     * @testdox Deletes payload keys from large batch of points
     */
    public function testDeletePayloadFromLargeBatch(): void
    {
        // Arrange
        $keys = ['temp'];
        $points = range(1, 100);
        $expectedResponse = [
            'result' => [
                'operation_id' => 7,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.015678,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/large_collection/points/payload/delete',
                [
                    'keys'   => $keys,
                    'points' => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePayload('large_collection', $keys, $points);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that deletePayload returns operation status
     *
     * @testdox Returns operation status after deleting payload keys
     */
    public function testDeletePayloadReturnsOperationStatus(): void
    {
        // Arrange
        $keys = ['old_field'];
        $points = [1, 2];
        $expectedResponse = [
            'result' => [
                'operation_id' => 8,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.002789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/payload/delete',
                [
                    'keys'   => $keys,
                    'points' => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePayload('vectors', $keys, $points);

        // Assert
        $this->assertEquals('completed', $result['result']['status']);
    }

    /**
     * Test that deletePayload returns complete API response
     *
     * @testdox Returns complete response with operation metadata
     */
    public function testDeletePayloadReturnsCompleteResponse(): void
    {
        // Arrange
        $keys = ['field1', 'field2'];
        $points = [1];
        $expectedResponse = [
            'result' => [
                'operation_id' => 9,
                'status'       => 'completed',
            ],
            'status' => 'ok',
            'time'   => 0.001456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/my_collection/points/payload/delete',
                [
                    'keys'   => $keys,
                    'points' => $points,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->deletePayload('my_collection', $keys, $points);

        // Assert
        $this->assertArrayHasKey('result', $result);
    }

    // ========================================================================
    // Scroll Points Tests
    // ========================================================================

    /**
     * Test that scroll retrieves points with default parameters
     *
     * @testdox Scrolls through points with default limit and payload included
     */
    public function testScrollPointsWithDefaultParameters(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'points' => [
                    ['id' => 1, 'payload' => ['city' => 'Berlin']],
                    ['id' => 2, 'payload' => ['city' => 'Moscow']],
                ],
                'next_page_offset' => 'offset_123',
            ],
            'status' => 'ok',
            'time'   => 0.002345,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/scroll',
                [
                    'limit'        => 100,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->scroll('test_collection');

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that scroll works with custom limit
     *
     * @testdox Scrolls through points with custom limit
     */
    public function testScrollPointsWithCustomLimit(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'points'           => [['id' => 1], ['id' => 2]],
                'next_page_offset' => 'offset_456',
            ],
            'status' => 'ok',
            'time'   => 0.001789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/scroll',
                [
                    'limit'        => 50,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->scroll('vectors', 50);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that scroll works with pagination offset
     *
     * @testdox Scrolls through points using pagination offset
     */
    public function testScrollPointsWithOffset(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'points'           => [['id' => 3], ['id' => 4]],
                'next_page_offset' => 'offset_789',
            ],
            'status' => 'ok',
            'time'   => 0.002456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/documents/points/scroll',
                [
                    'limit'        => 100,
                    'with_payload' => true,
                    'with_vector'  => false,
                    'offset'       => 'offset_123',
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->scroll('documents', 100, null, 'offset_123');

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that scroll works with filter
     *
     * @testdox Scrolls through points with filter applied
     */
    public function testScrollPointsWithFilter(): void
    {
        // Arrange
        $filter = [
            'must' => [
                ['key' => 'city', 'match' => ['value' => 'Berlin']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                'points'           => [['id' => 1, 'payload' => ['city' => 'Berlin']]],
                'next_page_offset' => null,
            ],
            'status' => 'ok',
            'time'   => 0.001234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/scroll',
                [
                    'limit'        => 100,
                    'with_payload' => true,
                    'with_vector'  => false,
                    'filter'       => $filter,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->scroll('test_collection', 100, $filter);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that scroll works with vectors included
     *
     * @testdox Scrolls through points with vectors included
     */
    public function testScrollPointsWithVectors(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'points' => [
                    [
                        'id'      => 1,
                        'vector'  => [0.1, 0.2, 0.3],
                        'payload' => ['city' => 'Berlin'],
                    ],
                ],
                'next_page_offset' => 'offset_abc',
            ],
            'status' => 'ok',
            'time'   => 0.003456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/embeddings/points/scroll',
                [
                    'limit'        => 100,
                    'with_payload' => true,
                    'with_vector'  => true,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->scroll('embeddings', 100, null, null, true, true);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that scroll works without payload
     *
     * @testdox Scrolls through points without payload
     */
    public function testScrollPointsWithoutPayload(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'points'           => [['id' => 1], ['id' => 2], ['id' => 3]],
                'next_page_offset' => 'offset_def',
            ],
            'status' => 'ok',
            'time'   => 0.001567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/scroll',
                [
                    'limit'        => 100,
                    'with_payload' => false,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->scroll('vectors', 100, null, null, false);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that scroll works with all parameters
     *
     * @testdox Scrolls through points with all parameters specified
     */
    public function testScrollPointsWithAllParameters(): void
    {
        // Arrange
        $filter = [
            'must' => [
                ['key' => 'category', 'match' => ['value' => 'tech']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                'points' => [
                    [
                        'id'      => 10,
                        'vector'  => [0.1, 0.2, 0.3, 0.4],
                        'payload' => ['category' => 'tech'],
                    ],
                ],
                'next_page_offset' => 'offset_xyz',
            ],
            'status' => 'ok',
            'time'   => 0.004567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/full_collection/points/scroll',
                [
                    'limit'        => 25,
                    'with_payload' => true,
                    'with_vector'  => true,
                    'filter'       => $filter,
                    'offset'       => 'prev_offset',
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->scroll('full_collection', 25, $filter, 'prev_offset', true, true);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that scroll returns null next_page_offset when no more pages
     *
     * @testdox Returns null next_page_offset when pagination is complete
     */
    public function testScrollReturnsNullOffsetWhenComplete(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'points'           => [['id' => 100]],
                'next_page_offset' => null,
            ],
            'status' => 'ok',
            'time'   => 0.000987,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/small_collection/points/scroll',
                [
                    'limit'        => 100,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->scroll('small_collection');

        // Assert
        $this->assertNull($result['result']['next_page_offset']);
    }

    /**
     * Test that scroll works with empty result
     *
     * @testdox Returns empty points array when no matches found
     */
    public function testScrollReturnsEmptyPoints(): void
    {
        // Arrange
        $filter = [
            'must' => [
                ['key' => 'nonexistent', 'match' => ['value' => 'test']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                'points'           => [],
                'next_page_offset' => null,
            ],
            'status' => 'ok',
            'time'   => 0.000456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/scroll',
                [
                    'limit'        => 100,
                    'with_payload' => true,
                    'with_vector'  => false,
                    'filter'       => $filter,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->scroll('test_collection', 100, $filter);

        // Assert
        $this->assertEmpty($result['result']['points']);
    }

    /**
     * Test that scroll returns complete API response
     *
     * @testdox Returns complete response with all metadata
     */
    public function testScrollReturnsCompleteResponse(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'points' => [
                    ['id' => 1, 'payload' => ['data' => 'test']],
                ],
                'next_page_offset' => 'next',
            ],
            'status' => 'ok',
            'time'   => 0.002123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/my_collection/points/scroll',
                [
                    'limit'        => 100,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->scroll('my_collection');

        // Assert
        $this->assertArrayHasKey('result', $result);
    }

    // ========================================================================
    // Count Points Tests
    // ========================================================================

    /**
     * Test that countPoints returns total count without filter
     *
     * @testdox Counts all points in collection without filter
     */
    public function testCountPointsWithoutFilter(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'count' => 1500,
            ],
            'status' => 'ok',
            'time'   => 0.000234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/count',
                null
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->countPoints('test_collection');

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that countPoints returns count with filter applied
     *
     * @testdox Counts points matching filter criteria
     */
    public function testCountPointsWithFilter(): void
    {
        // Arrange
        $filter = [
            'must' => [
                ['key' => 'city', 'match' => ['value' => 'Berlin']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                'count' => 250,
            ],
            'status' => 'ok',
            'time'   => 0.001234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/count',
                ['filter' => $filter]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->countPoints('test_collection', $filter);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that countPoints works with complex filter
     *
     * @testdox Counts points with complex filter conditions
     */
    public function testCountPointsWithComplexFilter(): void
    {
        // Arrange
        $filter = [
            'must' => [
                ['key' => 'category', 'match' => ['value' => 'tech']],
                ['key' => 'status', 'match' => ['value' => 'active']],
            ],
            'should' => [
                ['key' => 'priority', 'match' => ['value' => 'high']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                'count' => 42,
            ],
            'status' => 'ok',
            'time'   => 0.002345,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/documents/points/count',
                ['filter' => $filter]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->countPoints('documents', $filter);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that countPoints returns zero for empty collection
     *
     * @testdox Returns zero count for empty collection
     */
    public function testCountPointsReturnsZeroForEmptyCollection(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'count' => 0,
            ],
            'status' => 'ok',
            'time'   => 0.000123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/empty_collection/points/count',
                null
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->countPoints('empty_collection');

        // Assert
        $this->assertEquals(0, $result['result']['count']);
    }

    /**
     * Test that countPoints returns zero when filter matches nothing
     *
     * @testdox Returns zero count when no points match filter
     */
    public function testCountPointsReturnsZeroWithNoMatches(): void
    {
        // Arrange
        $filter = [
            'must' => [
                ['key' => 'nonexistent', 'match' => ['value' => 'test']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                'count' => 0,
            ],
            'status' => 'ok',
            'time'   => 0.000456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/count',
                ['filter' => $filter]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->countPoints('test_collection', $filter);

        // Assert
        $this->assertEquals(0, $result['result']['count']);
    }

    /**
     * Test that countPoints works with different collection names
     *
     * @testdox Counts points from specific collection
     */
    public function testCountPointsFromSpecificCollection(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'count' => 5000,
            ],
            'status' => 'ok',
            'time'   => 0.000789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/production_vectors/points/count',
                null
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->countPoints('production_vectors');

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that countPoints returns count value from result
     *
     * @testdox Returns count value in result field
     */
    public function testCountPointsReturnsCountValue(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'count' => 999,
            ],
            'status' => 'ok',
            'time'   => 0.000567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/count',
                null
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->countPoints('vectors');

        // Assert
        $this->assertArrayHasKey('count', $result['result']);
    }

    /**
     * Test that countPoints returns complete API response
     *
     * @testdox Returns complete response with all metadata
     */
    public function testCountPointsReturnsCompleteResponse(): void
    {
        // Arrange
        $expectedResponse = [
            'result' => [
                'count' => 12345,
            ],
            'status' => 'ok',
            'time'   => 0.001234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/my_collection/points/count',
                null
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->countPoints('my_collection');

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that countPoints works with range filter
     *
     * @testdox Counts points with range filter conditions
     */
    public function testCountPointsWithRangeFilter(): void
    {
        // Arrange
        $filter = [
            'must' => [
                [
                    'key'   => 'price',
                    'range' => [
                        'gte' => 10,
                        'lte' => 100,
                    ],
                ],
            ],
        ];

        $expectedResponse = [
            'result' => [
                'count' => 150,
            ],
            'status' => 'ok',
            'time'   => 0.001567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/products/points/count',
                ['filter' => $filter]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->countPoints('products', $filter);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that countPoints works with geo filter
     *
     * @testdox Counts points with geo filter conditions
     */
    public function testCountPointsWithGeoFilter(): void
    {
        // Arrange
        $filter = [
            'must' => [
                [
                    'key'        => 'location',
                    'geo_radius' => [
                        'center' => [
                            'lon' => 13.404954,
                            'lat' => 52.520008,
                        ],
                        'radius' => 1000,
                    ],
                ],
            ],
        ];

        $expectedResponse = [
            'result' => [
                'count' => 75,
            ],
            'status' => 'ok',
            'time'   => 0.002123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/locations/points/count',
                ['filter' => $filter]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->countPoints('locations', $filter);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    // ========================================================================
    // Search Tests
    // ========================================================================

    /**
     * Test that search performs vector similarity search with default parameters
     *
     * @testdox Searches for similar vectors with default parameters
     */
    public function testSearchWithDefaultParameters(): void
    {
        // Arrange
        $vector = [0.1, 0.2, 0.3, 0.4];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 1,
                    'score'   => 0.95,
                    'payload' => ['city' => 'Berlin'],
                ],
                [
                    'id'      => 2,
                    'score'   => 0.87,
                    'payload' => ['city' => 'Moscow'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.003456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/search',
                [
                    'vector'       => $vector,
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('test_collection', $vector);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that search works with custom limit
     *
     * @testdox Searches with custom result limit
     */
    public function testSearchWithCustomLimit(): void
    {
        // Arrange
        $vector = [0.5, 0.6, 0.7];
        $expectedResponse = [
            'result' => [
                ['id' => 1, 'score' => 0.98],
                ['id' => 2, 'score' => 0.92],
                ['id' => 3, 'score' => 0.85],
            ],
            'status' => 'ok',
            'time'   => 0.002345,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/search',
                [
                    'vector'       => $vector,
                    'limit'        => 3,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('vectors', $vector, 3);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that search works with filter
     *
     * @testdox Searches with filter conditions applied
     */
    public function testSearchWithFilter(): void
    {
        // Arrange
        $vector = [0.1, 0.2, 0.3];
        $filter = [
            'must' => [
                ['key' => 'city', 'match' => ['value' => 'Berlin']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                [
                    'id'      => 1,
                    'score'   => 0.95,
                    'payload' => ['city' => 'Berlin'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.001234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/search',
                [
                    'vector'       => $vector,
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                    'filter'       => $filter,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('test_collection', $vector, 10, $filter);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that search works with vectors included
     *
     * @testdox Searches with vectors included in response
     */
    public function testSearchWithVectors(): void
    {
        // Arrange
        $vector = [0.1, 0.2, 0.3, 0.4, 0.5];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 10,
                    'score'   => 0.99,
                    'vector'  => [0.11, 0.21, 0.31, 0.41, 0.51],
                    'payload' => ['category' => 'tech'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.004567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/embeddings/points/search',
                [
                    'vector'       => $vector,
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => true,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('embeddings', $vector, 10, null, true, true);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that search works without payload
     *
     * @testdox Searches without payload in response
     */
    public function testSearchWithoutPayload(): void
    {
        // Arrange
        $vector = [0.7, 0.8, 0.9];
        $expectedResponse = [
            'result' => [
                ['id' => 1, 'score' => 0.95],
                ['id' => 2, 'score' => 0.88],
            ],
            'status' => 'ok',
            'time'   => 0.002123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/search',
                [
                    'vector'       => $vector,
                    'limit'        => 10,
                    'with_payload' => false,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('vectors', $vector, 10, null, false);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that search works with score threshold
     *
     * @testdox Searches with minimum score threshold
     */
    public function testSearchWithScoreThreshold(): void
    {
        // Arrange
        $vector = [0.1, 0.2, 0.3];
        $expectedResponse = [
            'result' => [
                ['id' => 1, 'score' => 0.95, 'payload' => ['quality' => 'high']],
                ['id' => 2, 'score' => 0.92, 'payload' => ['quality' => 'high']],
            ],
            'status' => 'ok',
            'time'   => 0.001567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/search',
                [
                    'vector'          => $vector,
                    'limit'           => 10,
                    'with_payload'    => true,
                    'with_vector'     => false,
                    'score_threshold' => 0.9,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('test_collection', $vector, 10, null, true, false, 0.9);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that search works with all parameters
     *
     * @testdox Searches with all parameters specified
     */
    public function testSearchWithAllParameters(): void
    {
        // Arrange
        $vector = [0.1, 0.2, 0.3, 0.4];
        $filter = [
            'must' => [
                ['key' => 'category', 'match' => ['value' => 'tech']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                [
                    'id'      => 5,
                    'score'   => 0.97,
                    'vector'  => [0.12, 0.22, 0.32, 0.42],
                    'payload' => ['category' => 'tech', 'name' => 'Document 5'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.005678,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/full_search/points/search',
                [
                    'vector'          => $vector,
                    'limit'           => 5,
                    'with_payload'    => true,
                    'with_vector'     => true,
                    'filter'          => $filter,
                    'score_threshold' => 0.95,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('full_search', $vector, 5, $filter, true, true, 0.95);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that search returns empty results when no matches found
     *
     * @testdox Returns empty results when no similar vectors found
     */
    public function testSearchReturnsEmptyResults(): void
    {
        // Arrange
        $vector = [0.9, 0.9, 0.9];
        $expectedResponse = [
            'result' => [],
            'status' => 'ok',
            'time'   => 0.000456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/search',
                [
                    'vector'          => $vector,
                    'limit'           => 10,
                    'with_payload'    => true,
                    'with_vector'     => false,
                    'score_threshold' => 0.99,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('test_collection', $vector, 10, null, true, false, 0.99);

        // Assert
        $this->assertEmpty($result['result']);
    }

    /**
     * Test that search returns results sorted by score
     *
     * @testdox Returns search results with score values
     */
    public function testSearchReturnsResultsWithScores(): void
    {
        // Arrange
        $vector = [0.5, 0.5, 0.5];
        $expectedResponse = [
            'result' => [
                ['id' => 1, 'score' => 0.95],
                ['id' => 2, 'score' => 0.87],
                ['id' => 3, 'score' => 0.75],
            ],
            'status' => 'ok',
            'time'   => 0.002789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/search',
                [
                    'vector'       => $vector,
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('vectors', $vector);

        // Assert
        $this->assertArrayHasKey('score', $result['result'][0]);
    }

    /**
     * Test that search works with complex filter
     *
     * @testdox Searches with complex filter conditions
     */
    public function testSearchWithComplexFilter(): void
    {
        // Arrange
        $vector = [0.1, 0.2, 0.3];
        $filter = [
            'must' => [
                ['key' => 'category', 'match' => ['value' => 'tech']],
                ['key' => 'status', 'match' => ['value' => 'active']],
            ],
            'should' => [
                ['key' => 'priority', 'match' => ['value' => 'high']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                [
                    'id'      => 10,
                    'score'   => 0.96,
                    'payload' => ['category' => 'tech', 'status' => 'active'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.003456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/documents/points/search',
                [
                    'vector'       => $vector,
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                    'filter'       => $filter,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('documents', $vector, 10, $filter);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that search works with different collection names
     *
     * @testdox Searches in specific collection
     */
    public function testSearchInSpecificCollection(): void
    {
        // Arrange
        $vector = [0.3, 0.4, 0.5];
        $expectedResponse = [
            'result' => [
                ['id' => 1, 'score' => 0.89],
            ],
            'status' => 'ok',
            'time'   => 0.001234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/production_vectors/points/search',
                [
                    'vector'       => $vector,
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('production_vectors', $vector);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that search returns complete API response
     *
     * @testdox Returns complete response with all metadata
     */
    public function testSearchReturnsCompleteResponse(): void
    {
        // Arrange
        $vector = [0.1, 0.2, 0.3];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 1,
                    'score'   => 0.95,
                    'payload' => ['data' => 'test'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.002345,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/my_collection/points/search',
                [
                    'vector'       => $vector,
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('my_collection', $vector);

        // Assert
        $this->assertArrayHasKey('result', $result);
    }

    /**
     * Test that search works with high-dimensional vectors
     *
     * @testdox Searches with high-dimensional vectors
     */
    public function testSearchWithHighDimensionalVectors(): void
    {
        // Arrange
        $vector = array_fill(0, 768, 0.1);
        $expectedResponse = [
            'result' => [
                ['id' => 1, 'score' => 0.92],
            ],
            'status' => 'ok',
            'time'   => 0.004567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/bert_embeddings/points/search',
                [
                    'vector'       => $vector,
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('bert_embeddings', $vector);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that search works with single result limit
     *
     * @testdox Searches with limit of 1 result
     */
    public function testSearchWithSingleResultLimit(): void
    {
        // Arrange
        $vector = [0.5, 0.6];
        $expectedResponse = [
            'result' => [
                ['id' => 1, 'score' => 0.98, 'payload' => ['best' => 'match']],
            ],
            'status' => 'ok',
            'time'   => 0.001123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/search',
                [
                    'vector'       => $vector,
                    'limit'        => 1,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->search('vectors', $vector, 1);

        // Assert
        $this->assertCount(1, $result['result']);
    }

    // ========================================================================
    // Recommend Tests
    // ========================================================================

    /**
     * Test that recommend works with only positive examples
     *
     * @testdox Recommends points based on positive examples
     */
    public function testRecommendWithPositiveExamples(): void
    {
        // Arrange
        $positive = [1, 5, 10];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 15,
                    'score'   => 0.92,
                    'payload' => ['category' => 'similar'],
                ],
                [
                    'id'      => 20,
                    'score'   => 0.88,
                    'payload' => ['category' => 'similar'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.004567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => [],
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('test_collection', $positive);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that recommend works with positive and negative examples
     *
     * @testdox Recommends points using both positive and negative examples
     */
    public function testRecommendWithPositiveAndNegativeExamples(): void
    {
        // Arrange
        $positive = [1, 5];
        $negative = [3, 7];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 12,
                    'score'   => 0.89,
                    'payload' => ['type' => 'recommended'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.003456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => $negative,
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('test_collection', $positive, $negative);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that recommend works with custom limit
     *
     * @testdox Recommends with custom result limit
     */
    public function testRecommendWithCustomLimit(): void
    {
        // Arrange
        $positive = [1, 2, 3];
        $expectedResponse = [
            'result' => [
                ['id' => 10, 'score' => 0.95],
                ['id' => 11, 'score' => 0.92],
                ['id' => 12, 'score' => 0.88],
            ],
            'status' => 'ok',
            'time'   => 0.002345,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => [],
                    'limit'        => 3,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('vectors', $positive, [], 3);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that recommend works with filter
     *
     * @testdox Recommends with filter conditions applied
     */
    public function testRecommendWithFilter(): void
    {
        // Arrange
        $positive = [5, 10];
        $filter = [
            'must' => [
                ['key' => 'category', 'match' => ['value' => 'tech']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                [
                    'id'      => 20,
                    'score'   => 0.93,
                    'payload' => ['category' => 'tech'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.003789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => [],
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                    'filter'       => $filter,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('test_collection', $positive, [], 10, $filter);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that recommend works with vectors included
     *
     * @testdox Recommends with vectors included in response
     */
    public function testRecommendWithVectors(): void
    {
        // Arrange
        $positive = [1, 2];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 10,
                    'score'   => 0.96,
                    'vector'  => [0.1, 0.2, 0.3],
                    'payload' => ['name' => 'Similar Item'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.005123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/embeddings/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => [],
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => true,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('embeddings', $positive, [], 10, null, true, true);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that recommend works without payload
     *
     * @testdox Recommends without payload in response
     */
    public function testRecommendWithoutPayload(): void
    {
        // Arrange
        $positive = [5, 15];
        $expectedResponse = [
            'result' => [
                ['id' => 25, 'score' => 0.91],
                ['id' => 30, 'score' => 0.85],
            ],
            'status' => 'ok',
            'time'   => 0.002567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => [],
                    'limit'        => 10,
                    'with_payload' => false,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('vectors', $positive, [], 10, null, false);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that recommend works with string IDs
     *
     * @testdox Recommends using string IDs (UUIDs)
     */
    public function testRecommendWithStringIds(): void
    {
        // Arrange
        $positive = ['uuid-123', 'uuid-456'];
        $negative = ['uuid-789'];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 'uuid-abc',
                    'score'   => 0.94,
                    'payload' => ['type' => 'similar'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.003234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/documents/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => $negative,
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('documents', $positive, $negative);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that recommend works with single positive example
     *
     * @testdox Recommends based on single positive example
     */
    public function testRecommendWithSinglePositive(): void
    {
        // Arrange
        $positive = [42];
        $expectedResponse = [
            'result' => [
                ['id' => 43, 'score' => 0.89, 'payload' => ['similar' => true]],
                ['id' => 44, 'score' => 0.82, 'payload' => ['similar' => true]],
            ],
            'status' => 'ok',
            'time'   => 0.002789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => [],
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('test_collection', $positive);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that recommend works with all parameters
     *
     * @testdox Recommends with all parameters specified
     */
    public function testRecommendWithAllParameters(): void
    {
        // Arrange
        $positive = [1, 2, 3];
        $negative = [4, 5];
        $filter = [
            'must' => [
                ['key' => 'status', 'match' => ['value' => 'active']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                [
                    'id'      => 10,
                    'score'   => 0.97,
                    'vector'  => [0.1, 0.2, 0.3, 0.4],
                    'payload' => ['status' => 'active', 'name' => 'Best Match'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.006789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/full_recommend/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => $negative,
                    'limit'        => 5,
                    'with_payload' => true,
                    'with_vector'  => true,
                    'filter'       => $filter,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('full_recommend', $positive, $negative, 5, $filter, true, true);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that recommend returns empty results when no matches
     *
     * @testdox Returns empty results when no recommendations found
     */
    public function testRecommendReturnsEmptyResults(): void
    {
        // Arrange
        $positive = [999];
        $expectedResponse = [
            'result' => [],
            'status' => 'ok',
            'time'   => 0.001234,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => [],
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('test_collection', $positive);

        // Assert
        $this->assertEmpty($result['result']);
    }

    /**
     * Test that recommend returns results with scores
     *
     * @testdox Returns recommendation results with score values
     */
    public function testRecommendReturnsResultsWithScores(): void
    {
        // Arrange
        $positive = [1, 2];
        $expectedResponse = [
            'result' => [
                ['id' => 10, 'score' => 0.95],
                ['id' => 11, 'score' => 0.87],
            ],
            'status' => 'ok',
            'time'   => 0.003456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/vectors/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => [],
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('vectors', $positive);

        // Assert
        $this->assertArrayHasKey('score', $result['result'][0]);
    }

    /**
     * Test that recommend works with complex filter
     *
     * @testdox Recommends with complex filter conditions
     */
    public function testRecommendWithComplexFilter(): void
    {
        // Arrange
        $positive = [5, 10];
        $filter = [
            'must' => [
                ['key' => 'category', 'match' => ['value' => 'tech']],
                ['key' => 'status', 'match' => ['value' => 'active']],
            ],
            'should' => [
                ['key' => 'priority', 'match' => ['value' => 'high']],
            ],
        ];

        $expectedResponse = [
            'result' => [
                [
                    'id'      => 25,
                    'score'   => 0.91,
                    'payload' => ['category' => 'tech', 'status' => 'active'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.004123,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/documents/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => [],
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                    'filter'       => $filter,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('documents', $positive, [], 10, $filter);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that recommend works with different collection names
     *
     * @testdox Recommends in specific collection
     */
    public function testRecommendInSpecificCollection(): void
    {
        // Arrange
        $positive = [1, 2];
        $expectedResponse = [
            'result' => [
                ['id' => 10, 'score' => 0.88],
            ],
            'status' => 'ok',
            'time'   => 0.002456,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/production_vectors/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => [],
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('production_vectors', $positive);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test that recommend returns complete API response
     *
     * @testdox Returns complete response with all metadata
     */
    public function testRecommendReturnsCompleteResponse(): void
    {
        // Arrange
        $positive = [1];
        $expectedResponse = [
            'result' => [
                [
                    'id'      => 5,
                    'score'   => 0.92,
                    'payload' => ['data' => 'test'],
                ],
            ],
            'status' => 'ok',
            'time'   => 0.002789,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/my_collection/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => [],
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('my_collection', $positive);

        // Assert
        $this->assertArrayHasKey('result', $result);
    }

    /**
     * Test that recommend works with multiple negative examples
     *
     * @testdox Recommends while avoiding multiple negative examples
     */
    public function testRecommendWithMultipleNegativeExamples(): void
    {
        // Arrange
        $positive = [1, 2];
        $negative = [10, 20, 30];
        $expectedResponse = [
            'result' => [
                ['id' => 5, 'score' => 0.89, 'payload' => ['type' => 'good']],
            ],
            'status' => 'ok',
            'time'   => 0.003567,
        ];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/collections/test_collection/points/recommend',
                [
                    'positive'     => $positive,
                    'negative'     => $negative,
                    'limit'        => 10,
                    'with_payload' => true,
                    'with_vector'  => false,
                ]
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->client->recommend('test_collection', $positive, $negative);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }
}
