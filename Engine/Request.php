<?php

namespace Engine;

/**
 * Class Request
 * @package Engine
 */
class Request
{
    private string $path;
    private string $controller;
    private string $action;
    private array $arguments = [];
    private array $queryParams = [];
    private array $postData = [];
    private array $serverData = [];

    public function __construct()
    {
        $this->serverData = $_SERVER;
        $this->queryParams = $_GET;
        $this->postData = $_POST;
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

        $pathSegments = explode('/', trim($this->path, '/'));

        $this->controller = $pathSegments[0] !== '' ? $pathSegments[0] : 'index';
        $this->action = ($pathSegments[1] ?? '') !== '' ? $pathSegments[1] : 'index';

        // Extract arguments from path segments
        if (count($pathSegments) > 2) {
            $this->arguments = array_slice($pathSegments, 2);
        }
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getPostData(): array
    {
        return $this->postData;
    }

    public function getMethod(): string
    {
        return $this->serverData['REQUEST_METHOD'] ?? 'GET';
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
        return $this->serverData[$headerKey] ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->serverData['HTTP_USER_AGENT'] ?? null;
    }

    public function getIpAddress(): ?string
    {
        return $this->serverData['REMOTE_ADDR'] ?? null;
    }
}
