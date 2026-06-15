<?php

namespace Engine;

readonly class RouteMatch
{
    public function __construct(
        private Route $route,
        private array $parameters = []
    ) {
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getHandler(): array
    {
        return $this->route->getHandler();
    }
}
