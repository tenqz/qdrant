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
}
