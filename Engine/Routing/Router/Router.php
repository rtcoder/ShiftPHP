<?php

namespace Engine\Routing\Router;

use Engine\Error\HttpError;
use Engine\Request;

class Router
{
    /** @var Route[] */
    private array $routes = [];

    public function get(string $path, array $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, array $handler): self
    {
        return $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, array $handler): self
    {
        return $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, array $handler): self
    {
        return $this->add('DELETE', $path, $handler);
    }

    public function add(string $method, string $path, array $handler): self
    {
        $this->routes[] = new Route($method, $path, $handler);

        return $this;
    }

    public function match(Request $request): RouteMatch
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $parameters = $route->matches($request);

            if ($parameters !== null) {
                return new RouteMatch($route, $parameters);
            }

            if ($route->matchesPath($request->getPath())) {
                $allowedMethods[] = $route->getMethod();
            }
        }

        if ($allowedMethods !== []) {
            $allowedMethods = array_values(array_unique($allowedMethods));

            throw new HttpError(
                'Method not allowed',
                405,
                null,
                ['Allow' => implode(', ', $allowedMethods)]
            );
        }

        throw new HttpError('Endpoint not found', 404);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
