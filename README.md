<p align="center">
<img src="logo.png" alt="Qdrant PHP Client Library" width="200">
</p>

<h1 align="center">Qdrant PHP Client</h1>

<p align="center">
<span style="font-size: 1.2em;">A simple PHP library for Qdrant vector database</span>
</p>

<p align="center">
<a href="https://github.com/tenqz/qdrant/actions"><img src="https://github.com/tenqz/qdrant/workflows/Tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/tenqz/qdrant"><img src="https://img.shields.io/packagist/dt/tenqz/qdrant" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/tenqz/qdrant"><img src="https://img.shields.io/packagist/v/tenqz/qdrant" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/tenqz/qdrant"><img src="https://img.shields.io/packagist/l/tenqz/qdrant" alt="License"></a>
</p>

## 📖 About

Qdrant PHP Client is a simple and easy library for working with [Qdrant](https://qdrant.tech/) vector database. It helps you store and search vectors (embeddings) for AI and machine learning applications.

**Version 1.0.0** includes complete API support for collections, points, search, and recommendations.

## ✨ Features

- 🚀 **Easy to use** — Simple API, clear code
- 🔍 **Vector Search** — Find similar items by vector similarity
- 📊 **Batch Operations** — Work with many items at once
- 🎯 **Filters** — Search with conditions (price, category, etc.)
- 💡 **Recommendations** — Get recommendations based on examples
- 🧪 **100% Tested** — All features have tests
- 🐘 **PHP 7.2+** — Works with old and new PHP versions
- ⚡ **Fast** — Uses cURL for speed

## 📋 Requirements

- PHP 7.2 or higher
- Extensions: `ext-curl`, `ext-json`
- Qdrant server (local or cloud)

## 📦 Installation

Install via Composer:

```bash
composer require tenqz/qdrant
```

## 🚀 Quick Start

```php
<?php

use Tenqz\Qdrant\QdrantClient;
use Tenqz\Qdrant\Transport\Infrastructure\Factory\CurlHttpClientFactory;

// 1. Create client
$factory = new CurlHttpClientFactory();
$httpClient = $factory->create('localhost', 6333);
$client = new QdrantClient($httpClient);

// 2. Create collection
$client->createCollection('my_collection', 4, 'Cosine');

// 3. Add vectors with data
$client->upsertPoints('my_collection', [
    ['id' => 1, 'vector' => [0.1, 0.2, 0.3, 0.4], 'payload' => ['city' => 'Berlin']],
    ['id' => 2, 'vector' => [0.5, 0.6, 0.7, 0.8], 'payload' => ['city' => 'London']],
]);

// 4. Search for similar vectors
$results = $client->search('my_collection', [0.2, 0.1, 0.9, 0.7], 5);
```

**See [examples/basicUsage.php](examples/basicUsage.php) and [examples/README.md](examples/README.md) for complete examples of all features.**

## 📚 API Methods

### Collections

| Method | Description | Example |
|--------|-------------|---------|
| `createCollection()` | Create new collection | `$client->createCollection('my_vectors', 128, 'Cosine')` |
| `getCollection()` | Get collection info | `$client->getCollection('my_vectors')` |
| `listCollections()` | List all collections | `$client->listCollections()` |
| `deleteCollection()` | Delete collection | `$client->deleteCollection('my_vectors')` |

### Points Operations

| Method | Description | Example |
|--------|-------------|---------|
| `upsertPoints()` | Add or update points | `$client->upsertPoints('my_vectors', $points)` |
| `getPoint()` | Get one point by ID | `$client->getPoint('my_vectors', 1)` |
| `getPoints()` | Get multiple points | `$client->getPoints('my_vectors', [1, 2, 3])` |
| `deletePoints()` | Delete points | `$client->deletePoints('my_vectors', [1, 2, 3])` |
| `setPayload()` | Update point metadata | `$client->setPayload('my_vectors', ['new' => true], [1, 2])` |
| `deletePayload()` | Delete metadata fields | `$client->deletePayload('my_vectors', ['old_field'], [1, 2])` |
| `scroll()` | Get points page by page | `$client->scroll('my_vectors', 100)` |
| `countPoints()` | Count points | `$client->countPoints('my_vectors')` |

### Search & Recommendations

| Method | Description | Example |
|--------|-------------|---------|
| `search()` | Find similar vectors | `$client->search('my_vectors', [0.1, 0.2], 10)` |
| `searchBatch()` | Multiple searches at once | `$client->searchBatch('my_vectors', [$query1, $query2])` |
| `recommend()` | Get recommendations | `$client->recommend('my_vectors', [1, 5], [3], 10)` |

## 💡 Examples

See [`examples/basicUsage.php`](examples/basicUsage.php) for complete examples of:
- Creating collections with different settings
- Adding and updating data
- Vector similarity search
- Search with filters (price, category, etc.)
- Recommendations (like/unlike items)
- Batch operations
- Pagination with scroll
- And more!

Run the example:
```bash
php examples/basicUsage.php
```

## 🛡️ Error Handling

```php
use Tenqz\Qdrant\Transport\Domain\Exception\TransportException;
use Tenqz\Qdrant\Transport\Domain\Exception\HttpException;
use Tenqz\Qdrant\Transport\Domain\Exception\NetworkException;

try {
    $result = $client->search('my_collection', [0.1, 0.2], 10);
    
} catch (HttpException $e) {
    // HTTP errors (404, 500, etc.)
    echo "HTTP Error {$e->getStatusCode()}: {$e->getMessage()}\n";
    print_r($e->getResponse());
    
} catch (NetworkException $e) {
    // Network problems (connection failed, timeout)
    echo "Network Error: {$e->getMessage()}\n";
    
} catch (TransportException $e) {
    // Other transport errors
    echo "Error: {$e->getMessage()}\n";
}
```

### Error Types

- **HttpException** — HTTP errors like 404 Not Found, 500 Server Error
- **NetworkException** — Connection problems, timeouts, DNS errors
- **SerializationException** — JSON parsing errors
- **TransportException** — Base class for all errors (catch-all)

## 🧪 Testing

Run tests:

```bash
composer install
composer test
```

## 📖 Documentation

- [Qdrant Documentation](https://qdrant.tech/documentation/)
- [API Reference](https://qdrant.github.io/qdrant/redoc/index.html)
- [Examples](examples/basicUsage.php)

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

Quick steps:
1. Fork the repository
2. Create a feature branch
3. Write tests for new features
4. Submit a pull request

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

## 📞 Contact

**Author:** Oleg Patsay  
**Email:** smmartbiz@gmail.com  
**GitHub:** [tenqz/qdrant](https://github.com/tenqz/qdrant)

---

⭐ **Like this project? Give it a star on GitHub!**
