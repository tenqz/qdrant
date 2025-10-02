<?php

declare(strict_types=1);

namespace Tenqz\Qdrant\Tests\Unit\Transport\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Tenqz\Qdrant\Transport\Domain\HttpClientInterface;
use Tenqz\Qdrant\Transport\Infrastructure\CurlHttpClient;
use Tenqz\Qdrant\Transport\Infrastructure\Factory\CurlHttpClientFactory;

/**
 * Unit tests for CurlHttpClientFactory
 */
class CurlHttpClientFactoryTest extends TestCase
{
    /**
     * Test that factory creates instance implementing HttpClientInterface.
     * This ensures factory returns correct contract type for dependency injection.
     *
     * @testdox Creates client implementing HttpClientInterface
     */
    public function testCreateReturnsHttpClientInterface(): void
    {
        // Arrange
        $factory = new CurlHttpClientFactory();

        // Act
        $client = $factory->create();

        // Assert
        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    /**
     * Test that factory creates specific CurlHttpClient implementation.
     * This verifies factory produces correct concrete class.
     *
     * @testdox Creates CurlHttpClient concrete implementation
     */
    public function testCreateReturnsCurlHttpClient(): void
    {
        // Arrange
        $factory = new CurlHttpClientFactory();

        // Act
        $client = $factory->create();

        // Assert
        $this->assertInstanceOf(CurlHttpClient::class, $client);
    }

    /**
     * Test that factory can create clients with various parameter combinations.
     * This ensures factory handles all supported configuration options correctly
     * including host, port, API key, timeout, and protocol scheme.
     *
     * @testdox Creates client with various parameter combinations
     * @dataProvider createParametersProvider
     */
    public function testCreateWithVariousParameters(
        string $host,
        int $port,
        ?string $apiKey,
        int $timeout,
        string $scheme
    ): void {
        // Arrange
        $factory = new CurlHttpClientFactory();

        // Act
        $client = $factory->create($host, $port, $apiKey, $timeout, $scheme);

        // Assert
        $this->assertInstanceOf(CurlHttpClient::class, $client);
    }

    /**
     * Data provider for testing various factory parameter combinations.
     * Covers common use cases: default config, custom hosts/ports,
     * API authentication, timeouts, and HTTPS connections.
     */
    public static function createParametersProvider(): array
    {
        return [
            'default parameters' => [
                'localhost',
                6333,
                null,
                30,
                'http',
            ],
            'custom host' => [
                'example.com',
                6333,
                null,
                30,
                'http',
            ],
            'custom port' => [
                'localhost',
                8080,
                null,
                30,
                'http',
            ],
            'with api key' => [
                'localhost',
                6333,
                'secret-api-key',
                30,
                'http',
            ],
            'custom timeout' => [
                'localhost',
                6333,
                null,
                60,
                'http',
            ],
            'https scheme' => [
                'localhost',
                6333,
                null,
                30,
                'https',
            ],
            'all custom parameters' => [
                'example.com',
                8080,
                'my-api-key',
                120,
                'https',
            ],
        ];
    }

    /**
     * Test that factory creates independent client instances.
     * This ensures each create() call returns a new object instance,
     * preventing unexpected shared state between clients.
     *
     * @testdox Creates independent client instances on multiple calls
     */
    public function testMultipleClientsAreNotSameInstance(): void
    {
        // Arrange
        $factory = new CurlHttpClientFactory();

        // Act
        $client1 = $factory->create('host1.com', 6333);
        $client2 = $factory->create('host2.com', 8080);

        // Assert
        $this->assertNotSame($client1, $client2);
    }
}
