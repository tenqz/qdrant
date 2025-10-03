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
}
