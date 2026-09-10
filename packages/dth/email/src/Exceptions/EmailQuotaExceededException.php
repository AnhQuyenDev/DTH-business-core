<?php

namespace Dth\Email\Exceptions;

use RuntimeException;

final class EmailQuotaExceededException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct($message);
    }
}
