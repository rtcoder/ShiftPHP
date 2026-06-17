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

function assertFileExists(string $path, string $message): void
{
    if (!is_file($path)) {
        throw new RuntimeException($message . "\nMissing file: {$path}");
    }
}

function assertDirectoryExists(string $path, string $message): void
{
    if (!is_dir($path)) {
        throw new RuntimeException($message . "\nMissing directory: {$path}");
    }
}

function assertStringContains(string $needle, string|false $haystack, string $message): void
{
    if (!is_string($haystack) || !str_contains($haystack, $needle)) {
        throw new RuntimeException($message . "\nNeedle: {$needle}\nHaystack: " . var_export($haystack, true));
    }
}

function makeTempModulesPath(): string
{
    $root = sys_get_temp_dir() . '/shift-create-' . bin2hex(random_bytes(6));
    $modules = $root . '/modules';

    mkdir($modules, 0775, true);

    return $modules;
}

function removeDirectory(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
            continue;
        }

        unlink($file->getPathname());
    }

    rmdir($path);
}
