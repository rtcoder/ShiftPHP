<?php

use Shift\Request;
use Shift\Response\Response;
use Shift\Response\ResponseEmitter;

final class CapturingEmitter extends ResponseEmitter
{
    public int $statusCode = 0;
    public array $headers = [];
    public string $content = '';

    public function emit(Response $response): void
    {
        $this->statusCode = $response->getStatusCode();
        $this->headers = $response->getHeaders();
        $this->content = $response->getContent();
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

function assertArrayHasKeyValue(string $key, mixed $expected, array $actual, string $message): void
{
    if (!array_key_exists($key, $actual) || $actual[$key] !== $expected) {
        throw new RuntimeException($message . "\nArray: " . var_export($actual, true));
    }
}

function makeRequest(string $method, string $uri, string $body = '', array $query = []): Request
{
    return new Request(
        [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri,
            'HTTP_AUTHORIZATION' => 'Bearer token',
        ],
        $query,
        [],
        $body
    );
}
