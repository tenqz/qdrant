# Qdrant PHP Client - Examples

Simple examples showing how to use Qdrant PHP Client.

## Requirements

- PHP 7.2 or higher
- Qdrant server running on `localhost:6333`

## Files

- **basicUsage.php** - Complete examples showing all API methods
- **ExampleHelper.php** - Helper class for console output formatting
- **README.md** - This file

## Running Examples

### Basic Usage - Complete Guide

The file `basicUsage.php` shows all main features:

```bash
php examples/basicUsage.php
```

> **Note:** Uses `ExampleHelper` class for nice console output with colors and formatting.

## What basicUsage.php Shows

### 1. **Collections**
- Create a collection
- Get collection information
- Delete a collection

### 2. **Adding Data**
- Insert points with vectors
- Add metadata (payload)
- Batch insert

### 3. **Vector Search**
- Basic similarity search
- Get top N results
- Understanding scores

### 4. **Search with Filters**
- Filter by conditions (price, category)
- Range filters (greater than, less than)
- Combine search and filters

### 5. **Get Points**
- Get point by ID
- Get multiple points
- Include/exclude payload and vectors

### 6. **Update Data**
- Update point metadata
- Batch updates

### 7. **Pagination**
- Scroll through points
- Use cursor for large collections

### 8. **Count Points**
- Count all points
- Count with filters

### 9. **Recommendations**
- Get similar items
- Use positive examples (like)
- Use negative examples (dislike)

### 10. **Batch Search**
- Multiple searches in one request
- Better performance
- Different parameters for each search

### 11. **Collection Info**
- Get collection status
- Check point count
- View configuration

### 12. **Delete**
- Delete specific points
- Batch delete

### 13. **Cleanup**
- Remove test data

## Code Examples

### Create Collection

```php
$client->createCollection(
    'my_collection',  // Collection name
    384,              // Vector size (e.g., BERT embeddings)
    'Cosine'          // Distance metric
);
```

### Add Points

```php
$points = [
    [
        'id'      => 1,
        'vector'  => [0.1, 0.2, 0.3, 0.4],
        'payload' => ['category' => 'tech', 'title' => 'AI Article']
    ]
];

$client->upsertPoints('my_collection', $points);
```

### Search Vectors

```php
$results = $client->search(
    'my_collection',
    [0.1, 0.2, 0.3, 0.4],  // Query vector
    10                      // Top 10 results
);
```

### Search with Filter

```php
$results = $client->search(
    'my_collection',
    [0.1, 0.2, 0.3, 0.4],
    10,
    [
        'must' => [
            ['key' => 'category', 'match' => ['value' => 'tech']]
        ]
    ]
);
```

### Recommendations

```php
$recommendations = $client->recommend(
    'my_collection',
    [1, 5, 10],    // Positive examples (like these)
    [3, 7],        // Negative examples (not like these)
    5              // Top 5 recommendations
);
```

### Batch Search

```php
$batchResults = $client->searchBatch('my_collection', [
    ['vector' => [0.1, 0.2, 0.3, 0.4], 'limit' => 5, 'with_payload' => true],
    ['vector' => [0.5, 0.6, 0.7, 0.8], 'limit' => 3, 'with_payload' => true],
]);
```

## Use Cases

### 1. Text Search
Search documents by meaning, not just keywords.

### 2. Recommendations
Recommend products, articles, or content based on user preferences.

### 3. Image Search
Find similar images using vector representations.

### 4. Duplicate Detection
Find duplicate or very similar records.

### 5. Clustering
Group similar items together.

## Distance Metrics

- **Cosine** — Best for text embeddings (most common choice)
- **Dot** — Faster for normalized vectors
- **Euclid** — Euclidean distance (L2 norm)
- **Manhattan** — Manhattan distance (L1 norm)

## Tips

1. **Choose right metric**: Use `Cosine` for text, `Euclid` for images
2. **Use filters**: Combine vector search with filters for better results
3. **Batch operations**: Use batch methods for better performance
4. **Test with real data**: Try with your actual embeddings

## Troubleshooting

**Error: Connection refused**
- Check Qdrant server is running: `docker ps`
- Check port 6333 is open

**Error: Collection not found**
- Create collection first with `createCollection()`

**Error: Invalid vector size**
- Make sure vector size matches collection configuration

## Links

- [Qdrant Documentation](https://qdrant.tech/documentation/)
- [API Reference](https://qdrant.github.io/qdrant/redoc/index.html)
- [Main README](../README.md)
