<?php

namespace Engine;

class Route
{
    private string $regex;
    private array $parameterNames = [];

    public function __construct(
        private string $method,
        private string $path,
        private array $handler
    ) {
        $this->method = strtoupper($method);
        $this->path = $this->normalizePath($path);
        $this->regex = $this->compileRegex($this->path);
    }

    public function matches(Request $request): ?array
    {
        if ($this->method !== $request->getMethod()) {
            return null;
        }

        if (!$this->matchesPath($request->getPath(), $matches)) {
            return null;
        }

        $parameters = [];
        foreach ($this->parameterNames as $name) {
            $parameters[$name] = urldecode($matches[$name]);
        }

        return $parameters;
    }

    public function matchesPath(string $path, ?array &$matches = null): bool
    {
        return preg_match($this->regex, $path, $matches) === 1;
    }

    public function getHandler(): array
    {
        return $this->handler;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    private function compileRegex(string $path): string
    {
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)}/', function (array $match): string {
            $this->parameterNames[] = $match[1];
            return '___SHIFT_PARAM_' . $match[1] . '___';
        }, $path);

        $regex = preg_quote($regex, '#');

        foreach ($this->parameterNames as $name) {
            $regex = str_replace('___SHIFT_PARAM_' . $name . '___', '(?P<' . $name . '>[^/]+)', $regex);
        }

        return '#^' . $regex . '$#';
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
