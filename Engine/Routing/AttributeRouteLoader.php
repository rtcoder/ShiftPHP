<?php

namespace Engine\Routing;

use Engine\Routing\Attributes\Route;
use Engine\Routing\Attributes\RoutePrefix;
use Engine\Routing\Router\Router;
use ReflectionClass;

class AttributeRouteLoader
{
    /**
     * @param class-string[] $controllerClasses
     */
    public function load(Router $router, array $controllerClasses): void
    {
        foreach ($controllerClasses as $controllerClass) {
            if (!class_exists($controllerClass)) {
                continue;
            }

            $this->loadControllerRoutes($router, $controllerClass);
        }
    }

    /**
     * @param class-string $controllerClass
     */
    private function loadControllerRoutes(Router $router, string $controllerClass): void
    {
        $reflectionClass = new ReflectionClass($controllerClass);
        $prefix = $this->getPrefix($reflectionClass);

        foreach ($reflectionClass->getMethods() as $method) {
            foreach ($method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                /** @var \Engine\Routing\Router\Route $route */
                $route = $attribute->newInstance();

                $router->add(
                    $route->method,
                    $this->joinPaths($prefix, $route->path),
                    [$controllerClass, $method->getName()]
                );
            }
        }
    }

    private function getPrefix(ReflectionClass $reflectionClass): string
    {
        $attributes = $reflectionClass->getAttributes(RoutePrefix::class);

        if ($attributes === []) {
            return '';
        }

        /** @var RoutePrefix $prefix */
        $prefix = $attributes[0]->newInstance();

        return $prefix->path;
    }

    private function joinPaths(string $prefix, string $path): string
    {
        $joined = trim($prefix, '/') . '/' . trim($path, '/');
        $joined = '/' . trim($joined, '/');

        return $joined === '/' ? '/' : rtrim($joined, '/');
    }
}
