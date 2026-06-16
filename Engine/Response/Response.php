<?php

namespace Shift\Response;

class Response
{
    public function __construct(
        private readonly string $content = '',
        private readonly int    $statusCode = 200,
        private readonly array $headers = []
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
