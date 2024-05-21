<?php

declare(strict_types=1);

namespace Application\Exceptions;

use Exception;
use Throwable;

class HttpException extends Exception
{
    public function __construct(
        private int $statusCode,
        string $message = '',
        ?Throwable $previous = null,
        int $code = 0,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
