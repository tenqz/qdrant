<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/ExampleHelper.php';

use Tenqz\Qdrant\QdrantClient;
use Tenqz\Qdrant\Transport\Domain\Exception\TransportException;
use Tenqz\Qdrant\Transport\Domain\Exception\HttpException;
use Tenqz\Qdrant\Transport\Domain\Exception\NetworkException;
use Tenqz\Qdrant\Transport\Infrastructure\Factory\CurlHttpClientFactory;

/**
 * Qdrant PHP Client - Basic Usage Examples
 * 
 * This file demonstrates all major features of the Qdrant PHP client:
 * - Collections management
 * - Points operations (insert, get, update, delete)
 * - Vector similarity search
 * - Filtering and recommendations
 * - Batch operations
 */

// ============================================================================
// Initialize Client
// ============================================================================

$factory = new CurlHttpClientFactory();
$httpClient = $factory->create('localhost', 6333);
$client = new QdrantClient($httpClient);

echo "\n🚀 Qdrant PHP Client - Basic Usage Examples\n";
ExampleHelper::separator('━', 70);

try {
    // ========================================================================
    // 1. COLLECTIONS MANAGEMENT
    // ========================================================================
    
    ExampleHelper::section('Step 1: Create Collection');
    ExampleHelper::info('Creating collection with 4-dimensional vectors and Cosine distance');
    
    $result = $client->createCollection('test_collection', 4, 'Cosine');
    ExampleHelper::success("Collection created: {$result['status']}");
    
    // ========================================================================
    // 2. INSERT POINTS
    // ========================================================================
    
    ExampleHelper::section('Step 2: Insert Points (Upsert)');
    $points = [
        [
            'id' => 1,
            'vector' => [0.05, 0.61, 0.76, 0.74],
            'payload' => [
                'city' => 'Berlin',
                'country' => 'Germany',
                'price' => 100,
            ],
        ],
        [
            'id' => 2,
            'vector' => [0.19, 0.81, 0.75, 0.11],
            'payload' => [
                'city' => 'London',
                'country' => 'UK',
                'price' => 200,
            ],
        ],
        [
            'id' => 3,
            'vector' => [0.36, 0.55, 0.47, 0.94],
            'payload' => [
                'city' => 'Moscow',
                'country' => 'Russia',
                'price' => 150,
            ],
        ],
        [
            'id' => 4,
            'vector' => [0.18, 0.01, 0.85, 0.80],
            'payload' => [
                'city' => 'Paris',
                'country' => 'France',
                'price' => 250,
            ],
        ],
    ];
    
    $result = $client->upsertPoints('test_collection', $points);
    ExampleHelper::success("Points inserted: {$result['status']}");
    ExampleHelper::info('Inserted ' . count($points) . ' points with vectors and metadata');
    
    // ========================================================================
    // 3. VECTOR SIMILARITY SEARCH
    // ========================================================================
    
    ExampleHelper::section('Step 3: Basic Vector Search');
    ExampleHelper::info('Searching for vectors similar to [0.2, 0.1, 0.9, 0.7]');
    
    $searchResults = $client->search(
        'test_collection',
        [0.2, 0.1, 0.9, 0.7], // Query vector
        3 // Limit to 3 results
    );
    
    echo "Top 3 similar cities:\n";
    foreach ($searchResults['result'] as $i => $point) {
        echo sprintf(
            "  %d. %s (Price: %d) - Similarity: %.4f\n",
            $i + 1,
            $point['payload']['city'],
            $point['payload']['price'],
            $point['score']
        );
    }
    
    // ========================================================================
    // 4. SEARCH WITH FILTERS
    // ========================================================================
    
    ExampleHelper::section('Step 4: Search with Filter (price > 150)');
    $filteredResults = $client->search(
        'test_collection',
        [0.2, 0.1, 0.9, 0.7],
        10,
        [
            'must' => [
                [
                    'key' => 'price',
                    'range' => ['gt' => 150],
                ],
            ],
        ]
    );
    
    echo "Results with price > 150:\n";
    foreach ($filteredResults['result'] as $point) {
        echo sprintf(
            "  • %s (Price: %d, Score: %.4f)\n",
            $point['payload']['city'],
            $point['payload']['price'],
            $point['score']
        );
    }
    
    // ========================================================================
    // 5. GET POINTS
    // ========================================================================
    
    ExampleHelper::section('Step 5: Get Point by ID');
    $point = $client->getPoint('test_collection', 1);
    ExampleHelper::success('Retrieved point #1: ' . $point['result']['payload']['city']);
    echo "Vector: [" . implode(', ', $point['result']['vector']) . "]\n";
    
    // ========================================================================
    // 6. UPDATE PAYLOAD
    // ========================================================================
    
    ExampleHelper::section('Step 6: Update Payload (setPayload)');
    ExampleHelper::info('Adding "updated" field to points 1 and 2');
    
    $client->setPayload('test_collection', ['updated' => true, 'timestamp' => time()], [1, 2]);
    ExampleHelper::success('Payload updated for 2 points');
    
    // ========================================================================
    // 7. SCROLL (Pagination)
    // ========================================================================
    
    ExampleHelper::section('Step 7: Scroll Through Points');
    $scrollResult = $client->scroll('test_collection', 2);
    ExampleHelper::info('Using cursor-based pagination (limit=2)');
    echo "First page:\n";
    foreach ($scrollResult['result']['points'] as $point) {
        echo sprintf(
            "  • %s (ID: %d)\n",
            $point['payload']['city'],
            $point['id']
        );
    }
    $nextOffset = $scrollResult['result']['next_page_offset'] ?? null;
    ExampleHelper::info('Next offset: ' . ($nextOffset ?: 'null (end of results)'));
    
    // ========================================================================
    // 8. COUNT POINTS
    // ========================================================================
    
    ExampleHelper::section('Step 8: Count Points');
    $countResult = $client->countPoints('test_collection');
    ExampleHelper::success("Total points in collection: {$countResult['result']['count']}");
    
    // Count with filter
    $countFiltered = $client->countPoints('test_collection', [
        'must' => [
            ['key' => 'price', 'range' => ['gte' => 200]],
        ],
    ]);
    ExampleHelper::info("Points with price >= 200: {$countFiltered['result']['count']}");
    
    // ========================================================================
    // 9. RECOMMENDATIONS
    // ========================================================================
    
    ExampleHelper::section('Step 9: Content-based Recommendations');
    ExampleHelper::info('Find points similar to Berlin, but not like Paris');
    
    $recommendations = $client->recommend(
        'test_collection',
        [1], // Positive: Like point 1 (Berlin)
        [4], // Negative: Unlike point 4 (Paris)
        3    // Top 3 recommendations
    );
    
    echo "Recommended cities:\n";
    foreach ($recommendations['result'] as $i => $point) {
        echo sprintf(
            "  %d. %s (Score: %.4f)\n",
            $i + 1,
            $point['payload']['city'],
            $point['score']
        );
    }
    
    // ========================================================================
    // 10. BATCH SEARCH
    // ========================================================================
    
    ExampleHelper::section('Step 10: Batch Search (Multiple Queries)');
    ExampleHelper::info('Performing 2 searches in one request');
    
    $batchResults = $client->searchBatch('test_collection', [
        ['vector' => [0.1, 0.2, 0.3, 0.4], 'limit' => 2, 'with_payload' => true],
        ['vector' => [0.9, 0.8, 0.7, 0.6], 'limit' => 2, 'with_payload' => true],
    ]);
    
    foreach ($batchResults['result'] as $i => $results) {
        echo "Query " . ($i + 1) . " results:\n";
        foreach ($results as $point) {
            $city = isset($point['payload']['city']) ? $point['payload']['city'] : 'Unknown';
            echo "  • {$city} (Score: " . number_format($point['score'], 4) . ")\n";
        }
    }
    
    // ========================================================================
    // 11. COLLECTION INFO
    // ========================================================================
    
    ExampleHelper::section('Step 11: Get Collection Info');
    $collectionInfo = $client->getCollection('test_collection');
    echo "Status: {$collectionInfo['result']['status']}\n";
    echo "Points count: {$collectionInfo['result']['points_count']}\n";
    echo "Vectors count: {$collectionInfo['result']['indexed_vectors_count']}\n";
    echo "Vector size: {$collectionInfo['result']['config']['params']['vectors']['size']}\n";
    echo "Distance: {$collectionInfo['result']['config']['params']['vectors']['distance']}\n";
    
    // ========================================================================
    // 12. DELETE OPERATIONS
    // ========================================================================
    
    ExampleHelper::section('Step 12: Delete Operations');
    
    // Delete specific points
    ExampleHelper::info('Deleting points 3 and 4');
    $client->deletePoints('test_collection', [3, 4]);
    ExampleHelper::success('2 points deleted');
    
    // Verify count
    $countAfterDelete = $client->countPoints('test_collection');
    ExampleHelper::info("Remaining points: {$countAfterDelete['result']['count']}");
    
    // ========================================================================
    // 13. CLEANUP
    // ========================================================================
    
    ExampleHelper::section('Step 13: Cleanup');
    ExampleHelper::info('Deleting collection...');
    $client->deleteCollection('test_collection');
    ExampleHelper::success('Collection deleted successfully');
    
    echo "\n";
    ExampleHelper::separator('━', 70);
    ExampleHelper::success('All examples completed successfully!');
    ExampleHelper::separator('━', 70);
    echo "\n";
    
} catch (HttpException $e) {
    echo "\n";
    ExampleHelper::separator('━', 70);
    ExampleHelper::error("HTTP Error: {$e->getMessage()}");
    echo "Status Code: {$e->getStatusCode()}\n";
    if ($e->getResponse()) {
        echo "\nAPI Response:\n";
        echo json_encode($e->getResponse(), JSON_PRETTY_PRINT) . "\n";
    }
    ExampleHelper::separator('━', 70);
    echo "\n";
    
} catch (NetworkException $e) {
    echo "\n";
    ExampleHelper::separator('━', 70);
    ExampleHelper::error("Network Error: {$e->getMessage()}");
    ExampleHelper::warning('Check that Qdrant server is running on localhost:6333');
    ExampleHelper::separator('━', 70);
    echo "\n";
    
} catch (TransportException $e) {
    echo "\n";
    ExampleHelper::separator('━', 70);
    ExampleHelper::error("Transport Error: {$e->getMessage()}");
    echo "Status Code: {$e->getStatusCode()}\n";
    ExampleHelper::separator('━', 70);
    echo "\n";
    
} catch (Exception $e) {
    echo "\n";
    ExampleHelper::separator('━', 70);
    ExampleHelper::error("Unexpected Error: {$e->getMessage()}");
    ExampleHelper::separator('━', 70);
    echo "\n";
}
