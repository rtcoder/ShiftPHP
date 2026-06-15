<?php

namespace Engine;

use Engine\Error\HttpError;

/**
 * Class Request
 * @package Engine
 */
class Request
{
    private string $path;
    private array $queryParams;
    private array $postData;
    private array $serverData;
    private array $routeParams = [];
    private ?array $jsonData = null;
    private string $rawBody;

    public function __construct(?array $serverData = null, ?array $queryParams = null, ?array $postData = null, ?string $rawBody = null)
    {
        $this->serverData = $serverData ?? $_SERVER;
        $this->queryParams = $queryParams ?? $_GET;
        $this->postData = $postData ?? $_POST;
        $this->rawBody = $rawBody ?? (string) file_get_contents('php://input');
        $this->parseRequest();
    }

    private function parseRequest(): void
    {
        $this->path = $this->serverData['REQUEST_URI'] ?? '/';

        // Remove query string from path
        if (str_contains($this->path, '?')) {
            $parts = explode('?', $this->path);
            $this->path = $parts[0];
        }

        $this->path = '/' . trim($this->path, '/');
        $this->path = $this->path === '/' ? '/' : rtrim($this->path, '/');

    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getPostData(): array
    {
        return $this->postData;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->postData[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $json = $this->getJson();

        if (array_key_exists($key, $json)) {
            return $json[$key];
        }

        if (array_key_exists($key, $this->postData)) {
            return $this->postData[$key];
        }

        return $this->query($key, $default);
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    public function getJson(): array
    {
        if ($this->jsonData !== null) {
            return $this->jsonData;
        }

        if (trim($this->rawBody) === '') {
            $this->jsonData = [];
            return $this->jsonData;
        }

        $decoded = json_decode($this->rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpError('Malformed JSON request body', 400);
        }

        $this->jsonData = is_array($decoded) ? $decoded : [];

        return $this->jsonData;
    }

    public function getMethod(): string
    {
        return strtoupper($this->serverData['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    public function getHeader(string $name): ?string
    {
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->serverData[$headerKey] ?? $this->serverData[strtoupper(str_replace('-', '_', $name))] ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->serverData['HTTP_USER_AGENT'] ?? null;
    }

    public function getIpAddress(): ?string
    {
        return $this->serverData['REMOTE_ADDR'] ?? null;
    }

    public function setRouteParams(array $routeParams): void
    {
        $this->routeParams = $routeParams;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function routeParam(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }
}
