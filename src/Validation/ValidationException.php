<?php

namespace Shift\Validation;

use Shift\Error\HttpError;

class ValidationException extends HttpError
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed', 422);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
