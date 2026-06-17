<?php

namespace Shift\Middleware;

use InvalidArgumentException;
use Shift\Request;
use Shift\Response\Response;
use Shift\Service\ServiceContainer;

final class MiddlewarePipeline
{
    public function __construct(private readonly ServiceContainer $container)
    {
    }

    /**
     * @param array<int, MiddlewareInterface|callable|class-string> $middleware
     */
    public function handle(Request $request, array $middleware, callable $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($middleware),
            fn (callable $next, mixed $middleware): callable => function (Request $request) use ($middleware, $next): Response {
                return $this->callMiddleware($middleware, $request, $next);
            },
            $destination
        );

        $response = $pipeline($request);

        if (!$response instanceof Response) {
            throw new InvalidArgumentException('Middleware pipeline must return a response.');
        }

        return $response;
    }

    private function callMiddleware(mixed $middleware, Request $request, callable $next): Response
    {
        if (is_string($middleware)) {
            if (!class_exists($middleware)) {
                throw new InvalidArgumentException("Middleware class '{$middleware}' not found.");
            }

            $middleware = $this->container->has($middleware)
                ? $this->container->resolve($middleware)
                : new $middleware();
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->handle($request, $next);
        }

        if (is_callable($middleware)) {
            $response = $middleware($request, $next);

            if (!$response instanceof Response) {
                throw new InvalidArgumentException('Middleware must return a response.');
            }

            return $response;
        }

        throw new InvalidArgumentException('Middleware must be a callable, class name, or MiddlewareInterface instance.');
    }
}
