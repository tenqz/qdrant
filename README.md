<p align="center">
<img src="logo.png" alt="Qdrant PHP Client Library" width="200">
</p>

<h1 align="center">Qdrant PHP Client Library</h1>

<p align="center">
<span style="font-size: 1.2em;">Documentation for version v0.2.0</span>
</p>

<p align="center">
<a href="https://github.com/tenqz/qdrant/actions"><img src="https://github.com/tenqz/qdrant/workflows/Tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/tenqz/qdrant"><img src="https://img.shields.io/packagist/dt/tenqz/qdrant" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/tenqz/qdrant"><img src="https://img.shields.io/packagist/v/tenqz/qdrant" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/tenqz/qdrant"><img src="https://img.shields.io/packagist/l/tenqz/qdrant" alt="License"></a>
</p>

## 📖 About

Qdrant PHP Client Library is a modern PHP library for working with the Qdrant vector database. The library is built on Clean Architecture and Domain-Driven Design (DDD) principles, making the code clear, extensible, and easy to test.

**Current version (v0.2.0)** includes the basic transport infrastructure for communicating with the Qdrant API.

## ✨ Key Features

- 🏗️ **Clean Architecture** — separation into Domain and Infrastructure layers
- 🔌 **Flexible Integration** — easily swap HTTP client implementations
- 🛡️ **Type Safety** — strict typing and comprehensive PHPDoc annotations
- 🧪 **100% Test Coverage** — all components are thoroughly tested
- 🐘 **Wide Compatibility** — works with PHP 7.2 - 8.x
- ⚡ **Performance** — built on cURL for fast HTTP requests

## 📋 Requirements

- PHP 7.2 or higher
- Extensions: `ext-curl`, `ext-json`
- Qdrant server instance

## 📦 Installation

Install the package via Composer:

```bash
composer require tenqz/qdrant
```

## 🏗️ Library Architecture

The library is built on **Clean Architecture** principles and divided into logical layers:

```
src/
├── QdrantClient.php              # Main client for Qdrant operations
└── Transport/                    # Transport layer (HTTP communication)
    ├── Domain/                   # Transport business logic (what to do)
    │   ├── HttpClientInterface.php        # HTTP client contract
    │   ├── Factory/
    │   │   └── HttpClientFactoryInterface.php  # Client factory interface
    │   └── Exception/            # Transport layer exceptions
    │       ├── TransportException.php     # Base exception
    │       ├── HttpException.php          # HTTP errors (4xx, 5xx)
    │       ├── NetworkException.php       # Network errors
    │       └── SerializationException.php # JSON errors
    │
    └── Infrastructure/           # Transport implementation (how to do)
        ├── CurlHttpClient.php    # cURL-based HTTP client
        └── Factory/
            └── CurlHttpClientFactory.php  # cURL client factory
```

### 🎯 Why This Structure?

**Domain Layer** — describes *"what needs to be done"*:
- Interfaces and contracts
- Business rules and logic
- Independent of specific implementations

**Infrastructure Layer** — describes *"how to do it"*:
- Concrete implementations of interfaces
- Integration with external systems (cURL, HTTP)
- Easy to replace with alternative implementations

### 🔄 How It Works

1. **Factory** (`CurlHttpClientFactory`) creates the HTTP client
2. **HTTP Client** (`CurlHttpClient`) sends requests to Qdrant API
3. **Error Handling** — specialized exceptions for different error types
4. **QdrantClient** uses the HTTP client to interact with the database

## 🚀 Quick Start

### Step 1: Create HTTP Client

Use the factory to create a client:

```php
<?php

use Tenqz\Qdrant\Transport\Infrastructure\Factory\CurlHttpClientFactory;

// Create factory
$factory = new CurlHttpClientFactory();

// Create HTTP client
$httpClient = $factory->create(
    host: 'localhost',     // Qdrant server address
    port: 6333,            // Port (default 6333)
    apiKey: null,          // API key (optional)
    timeout: 30,           // Timeout in seconds
    scheme: 'http'         // Protocol: http or https
);
```

### Step 2: Initialize Qdrant Client

```php
<?php

use Tenqz\Qdrant\QdrantClient;

// Create main Qdrant client
$client = new QdrantClient($httpClient);
```

### Step 3: Working with API (Coming Soon)

> ⚠️ **Note**: Version v0.2.0 includes only the transport layer. 
> Methods for working with collections, vectors, and search will be available in future versions.

For now, you can use low-level API access through the HTTP client:

```php
<?php

use Tenqz\Qdrant\Transport\Domain\Exception\TransportException;

try {
    // Direct HTTP request to API
    $response = $httpClient->request(
        'GET',                    // HTTP method
        '/collections',           // API endpoint path
        null                      // Request data (null for GET)
    );
    
    print_r($response);
    
} catch (TransportException $e) {
    echo "Error: " . $e->getMessage();
}
```

## 🛡️ Error Handling

The library provides specialized exceptions for different error types:

```php
<?php

use Tenqz\Qdrant\Transport\Domain\Exception\HttpException;
use Tenqz\Qdrant\Transport\Domain\Exception\NetworkException;
use Tenqz\Qdrant\Transport\Domain\Exception\SerializationException;
use Tenqz\Qdrant\Transport\Domain\Exception\TransportException;

try {
    $response = $httpClient->request('GET', '/collections');
    
} catch (HttpException $e) {
    // HTTP errors (400, 404, 500, etc.)
    echo "HTTP error {$e->getStatusCode()}: {$e->getMessage()}";
    print_r($e->getResponse()); // Full server response
    
} catch (NetworkException $e) {
    // Network errors (connection failed, timeout)
    echo "Network error: {$e->getMessage()}";
    
} catch (SerializationException $e) {
    // JSON errors (invalid format)
    echo "JSON error: {$e->getMessage()}";
    
} catch (TransportException $e) {
    // Any other transport errors
    echo "Transport error: {$e->getMessage()}";
}
```

### 📚 Exception Types

| Exception | When It Occurs | Example |
|-----------|----------------|---------|
| `HttpException` | HTTP errors 4xx/5xx | 404 Not Found, 500 Internal Error |
| `NetworkException` | Network problems | Connection failed, timeout, DNS errors |
| `SerializationException` | JSON problems | Invalid JSON in request/response |
| `TransportException` | Base class for all errors | Use for general catch |

## 🧪 Testing

The library is fully covered with unit tests. To run tests:

```bash
# Install dependencies
composer install

# Run all checks and tests
composer test

# Or separately:
composer phpunit          # PHPUnit tests only
composer analyze          # PHPStan code analysis
composer check-style      # Code style check
```

## 📊 Code Quality

The project uses modern tools for quality control:

- **PHPUnit** — unit testing (100% transport layer coverage)
- **PHPStan** (level 5) — static code analysis
- **PHP CS Fixer** — automatic code formatting
- **PHP_CodeSniffer** — coding standards verification

## 🤝 Contributing

We welcome contributions! Please read our [Contributing Guide](CONTRIBUTING.md) for details on:

- Code style and quality standards
- Commit message conventions
- Testing requirements
- Pull request process

Quick commands:
```bash
composer test           # Run all checks
composer run phpunit    # Run tests only
composer run analyze    # Static analysis
composer run cs-fix     # Auto-fix code style
```

## 📄 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## 📞 Contact

**Author:** Oleg Patsay  
**Email:** smmartbiz@gmail.com  
**GitHub:** [tenqz/qdrant](https://github.com/tenqz/qdrant)

---

⭐ If you find this project useful, give it a star on GitHub!

