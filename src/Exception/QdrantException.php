<?php

declare(strict_types=1);

namespace Tenqz\Qdrant\Exception;

use Exception;

/**
 * Base exception for all Qdrant-related errors
 */
class QdrantException extends Exception
{
    /** @var int */
    private $statusCode;

    /** @var array|null */
    private $response;

    /**
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array|null $response Full response from Qdrant API
     */
    public function __construct(string $message, int $statusCode = 0, ?array $response = null)
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->response = $response;
    }

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get full API response
     *
     * @return array|null
     */
    public function getResponse(): ?array
    {
        return $this->response;
    }
}
