<?php

declare(strict_types=1);

namespace Tenqz\Qdrant\Transport;

use Tenqz\Qdrant\Exception\QdrantException;

/**
 * cURL-based HTTP client implementation
 */
class CurlHttpClient implements HttpClientInterface
{
    /** @var string */
    private $baseUrl;

    /** @var string|null */
    private $apiKey;

    /** @var int */
    private $timeout;

    /**
     * @param string $baseUrl Base URL for API requests
     * @param string|null $apiKey Optional API key for authentication
     * @param int $timeout Request timeout in seconds
     */
    public function __construct(string $baseUrl, ?string $apiKey = null, int $timeout = 30)
    {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    /**
     * Execute HTTP request
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $path API endpoint path
     * @param array|null $data Request body data
     * @return array Response data
     * @throws QdrantException
     */
    public function request(string $method, string $path, ?array $data = null): array
    {
        $ch = $this->prepareCurlRequest($method, $path, $data);
        [$response, $httpCode, $error] = $this->executeRequest($ch);

        return $this->handleResponse($response, $httpCode, $error);
    }

    /**
     * Prepare cURL request with all necessary options
     *
     * @param string $method HTTP method
     * @param string $path API endpoint path
     * @param array|null $data Request body data
     * @return resource cURL handle
     * @throws QdrantException
     */
    private function prepareCurlRequest(string $method, string $path, ?array $data)
    {
        $url = "{$this->baseUrl}{$path}";
        $ch = curl_init();

        $this->setCommonOptions($ch, $url, $method);
        $this->setHeaders($ch);
        $this->setBody($ch, $method, $data);

        return $ch;
    }

    /**
     * Set common cURL options
     *
     * @param resource $ch cURL handle
     * @param string $url Request URL
     * @param string $method HTTP method
     * @return void
     */
    private function setCommonOptions($ch, string $url, string $method): void
    {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }

    /**
     * Set request headers
     *
     * @param resource $ch cURL handle
     * @return void
     */
    private function setHeaders($ch): void
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($this->apiKey !== null) {
            $headers[] = "api-key: {$this->apiKey}";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set request body for POST/PUT/PATCH requests
     *
     * @param resource $ch cURL handle
     * @param string $method HTTP method
     * @param array|null $data Request body data
     * @return void
     * @throws QdrantException
     */
    private function setBody($ch, string $method, ?array $data): void
    {
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $bodyData = $data ?? new \stdClass();
            $jsonData = json_encode($bodyData);
            if ($jsonData === false) {
                throw new QdrantException('Failed to encode request data as JSON');
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
    }

    /**
     * Execute cURL request
     *
     * @param resource $ch cURL handle
     * @return array [response, httpCode, error]
     */
    private function executeRequest($ch): array
    {
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [$response, $httpCode, $error];
    }

    /**
     * Handle HTTP response and errors
     *
     * @param mixed $response cURL response
     * @param int $httpCode HTTP status code
     * @param string $error cURL error message
     * @return array Decoded response
     * @throws QdrantException
     */
    private function handleResponse($response, int $httpCode, string $error): array
    {
        $this->checkCurlErrors($response, $error);
        $decodedResponse = $this->parseJsonResponse($response);
        $this->checkHttpStatusCode($httpCode, $decodedResponse);

        return $decodedResponse;
    }

    /**
     * Check for cURL errors
     *
     * @param mixed $response cURL response
     * @param string $error cURL error message
     * @return void
     * @throws QdrantException
     */
    private function checkCurlErrors($response, string $error): void
    {
        if ($response === false) {
            throw new QdrantException("cURL error: {$error}");
        }
    }

    /**
     * Parse JSON response
     *
     * @param mixed $response cURL response
     * @return array Decoded response
     * @throws QdrantException
     */
    private function parseJsonResponse($response): array
    {
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new QdrantException('Failed to decode JSON response: ' . json_last_error_msg());
        }

        return $decodedResponse;
    }

    /**
     * Check HTTP status code
     *
     * @param int $httpCode HTTP status code
     * @param array $decodedResponse Decoded response
     * @return void
     * @throws QdrantException
     */
    private function checkHttpStatusCode(int $httpCode, array $decodedResponse): void
    {
        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['status']['error'] ?? 'Unknown error';

            throw new QdrantException(
                "Qdrant API error (HTTP {$httpCode}): {$errorMessage}",
                $httpCode,
                $decodedResponse
            );
        }
    }
}
