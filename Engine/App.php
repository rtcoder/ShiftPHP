<?php

namespace Engine;

use Engine\Error\HttpError;
use JsonException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

/**
 * Class App
 * @package Engine
 */
final class App
{
    private Request $request;
    private ServiceContainer $container;
    private Router $router;
    private ResponseEmitter $emitter;

    public function __construct(Request $request, ?Router $router = null, ?ResponseEmitter $emitter = null)
    {
        $this->request = $request;
        $this->container = new ServiceContainer();
        $this->router = $router ?? new Router();
        $this->emitter = $emitter ?? new ResponseEmitter();
        $this->registerDefaultServices();
    }

    public function start(): void
    {
        try {
            $response = $this->dispatch();
        } catch (HttpError $exception) {
            $response = JsonResponse::error(
                $exception->getMessage(),
                $exception->getStatusCode(),
                [],
                $exception->getHeaders()
            );
        } catch (Throwable $exception) {
            $response = JsonResponse::error('Internal Server Error', 500);
        }

        $this->emitter->emit($response);
    }

    /**
     * @throws ReflectionException
     */
    private function dispatch(): Response
    {
        $match = $this->router->match($this->request);
        $this->request->setRouteParams($match->getParameters());

        [$controllerClass, $methodName] = $match->getHandler();

        if (!class_exists($controllerClass)) {
            throw new HttpError('Endpoint not found', 404);
        }

        $controller = new $controllerClass($this->request, $this->container);
        $reflectionClass = new ReflectionClass($controller);

        if (!$reflectionClass->hasMethod($methodName)) {
            throw new HttpError('Endpoint not found', 404);
        }

        $method = $reflectionClass->getMethod($methodName);
        $result = $method->invokeArgs(
            $controller,
            $this->resolveMethodArguments($method->getParameters(), $match->getParameters())
        );

        return $this->normalizeResponse($result);
    }

    /**
     * @param ReflectionParameter[] $parameters
     */
    private function resolveMethodArguments(array $parameters, array $routeParameters): array
    {
        $arguments = [];
        $orderedRouteParameters = array_values($routeParameters);

        foreach ($parameters as $index => $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && $type->getName() === Request::class) {
                $arguments[] = $this->request;
                continue;
            }

            if (array_key_exists($parameter->getName(), $routeParameters)) {
                $arguments[] = $routeParameters[$parameter->getName()];
                continue;
            }

            if (array_key_exists($index, $orderedRouteParameters)) {
                $arguments[] = $orderedRouteParameters[$index];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new HttpError('Endpoint not found', 404);
        }

        return $arguments;
    }

    /**
     * @throws JsonException
     */
    private function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return new JsonResponse($result);
        }

        if ($result === null) {
            return new Response('', 204);
        }

        return new Response((string) $result);
    }

    /**
     * @param string $class_name
     */
    public static function autoload(string $class_name): void
    {
        $class = str_replace('_', '/',
            str_replace('\\', '/', $class_name)
        );

        if (empty($class)) {
            return;
        }

        $locations = [
            APP_PATH . '/controller/',
            APP_PATH . '/model/',
            APP_ROOT,
        ];

        foreach ($locations as $location) {
            if (file_exists($location . $class . '.php')) {
                require_once($location . $class . '.php');
            }
        }
    }

    public static function setHelpers(): void
    {
        require_once 'Utils/helpers.php';
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    private function registerDefaultServices(): void
    {
        $this->container->singleton('request', $this->request);
        $this->container->singleton('router', $this->router);
    }

    public function getContainer(): ServiceContainer
    {
        return $this->container;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function resolve(string $service)
    {
        return $this->container->resolve($service);
    }
}
