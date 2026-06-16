<?php

namespace Engine;

use Engine\Error\HttpError;
use Engine\Response\Response;
use Engine\Response\ResponseEmitter;
use Engine\Routing\Attributes\Body;
use Engine\Routing\Attributes\Header;
use Engine\Routing\Attributes\PathParam;
use Engine\Routing\Attributes\QueryParam;
use Engine\Routing\Attributes\Status;
use JsonException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
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

        return $this->applyResponseAttributes(
            $this->normalizeResponse($result),
            $method
        );
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

            $pathParam = $this->getParameterAttribute($parameter, PathParam::class);
            if ($pathParam instanceof PathParam) {
                $name = $pathParam->name ?? $parameter->getName();
                $arguments[] = $this->castParameterValue(
                    $routeParameters[$name] ?? $this->getDefaultParameterValue($parameter),
                    $parameter
                );
                continue;
            }

            $queryParam = $this->getParameterAttribute($parameter, QueryParam::class);
            if ($queryParam instanceof QueryParam) {
                $name = $queryParam->name ?? $parameter->getName();
                $arguments[] = $this->castParameterValue(
                    $this->request->query($name, $queryParam->default ?? $this->getDefaultParameterValue($parameter)),
                    $parameter
                );
                continue;
            }

            $body = $this->getParameterAttribute($parameter, Body::class);
            if ($body instanceof Body) {
                $json = $this->request->getJson();
                $value = $body->key === null
                    ? $json
                    : ($json[$body->key] ?? $this->getDefaultParameterValue($parameter));
                $arguments[] = $this->castParameterValue($value, $parameter);
                continue;
            }

            if (array_key_exists($parameter->getName(), $routeParameters)) {
                $arguments[] = $this->castParameterValue($routeParameters[$parameter->getName()], $parameter);
                continue;
            }

            if (array_key_exists($index, $orderedRouteParameters)) {
                $arguments[] = $this->castParameterValue($orderedRouteParameters[$index], $parameter);
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

    private function getParameterAttribute(ReflectionParameter $parameter, string $attributeClass): ?object
    {
        $attributes = $parameter->getAttributes($attributeClass);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    private function getDefaultParameterValue(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return null;
    }

    private function castParameterValue(mixed $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $value === null) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            default => $value,
        };
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

    private function applyResponseAttributes(Response $response, ReflectionMethod $method): Response
    {
        $statusCode = $response->getStatusCode();
        $statusAttributes = $method->getAttributes(Status::class);

        if ($statusAttributes !== []) {
            /** @var Status $status */
            $status = $statusAttributes[0]->newInstance();
            $statusCode = $status->code;
        }

        $headers = $response->getHeaders();
        foreach ($method->getAttributes(Header::class) as $attribute) {
            /** @var Header $header */
            $header = $attribute->newInstance();
            $headers[$header->name] = $header->value;
        }

        return new Response(
            $response->getContent(),
            $statusCode,
            $headers
        );
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
