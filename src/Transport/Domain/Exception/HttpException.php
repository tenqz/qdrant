<?php

declare(strict_types=1);

namespace Tenqz\Qdrant\Transport\Domain\Exception;

/**
 * Exception for HTTP-level errors (4xx, 5xx responses)
 */
class HttpException extends TransportException
{
}
