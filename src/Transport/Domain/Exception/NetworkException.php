<?php

declare(strict_types=1);

namespace Tenqz\Qdrant\Transport\Domain\Exception;

/**
 * Exception for network-level errors (connection, timeout, DNS)
 */
class NetworkException extends TransportException
{
}
