<?php

namespace Engine\Error;

use Throwable;

class HttpError extends ShiftError
{
    public function __construct(string $message = '', int $statusCode = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        $code = $this->getCode();

        if ($code < 400 || $code > 599) {
            return 500;
        }

        return $code;
    }
}
