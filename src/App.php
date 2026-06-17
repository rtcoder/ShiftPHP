<?php

namespace Shift;

use Shift\Error\HttpError;
use Shift\Database\Database;
use Shift\Database\DatabaseConfig;
use Shift\Logging\ExceptionLogger;
use Shift\Logging\LoggerFactory;
use Shift\Logging\LoggerInterface;
use Shift\Middleware\MiddlewareInterface;
use Shift\Middleware\MiddlewarePipeline;
use Shift\Response\JsonResponse;
use Shift\Response\Response;
use Shift\Response\ResponseEmitter;
use Shift\Routing\Attributes\Body;
use Shift\Routing\Attributes\BodyDto;
use Shift\Routing\Attributes\Header;
use Shift\Routing\Attributes\PathParam;
use Shift\Routing\Attributes\QueryParam;
use Shift\Routing\Attributes\Status;
use Shift\Routing\Router\Router;
use Shift\Service\ServiceContainer;
use Shift\Validation\RequestDto;
use Shift\Validation\ValidationException;
use JsonException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

/**
 * Class App
 * @package Shift
 */
final class App
{
    private Request $request;
    private ServiceContainer $container;
    private Router $router;
    private ResponseEmitter $emitter;
    private array $middleware = [];

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
            $response = $this->handleRequest();
        } catch (ValidationException $exception) {
            $this->logException($exception, $exception->getStatusCode());
            $response = JsonResponse::error(
                $exception->getMessage(),
                $exception->getStatusCode(),
                ['errors' => $exception->getErrors()],
                $exception->getHeaders()
            );
        } catch (HttpError $exception) {
            $this->logException($exception, $exception->getStatusCode());
            $response = JsonResponse::error(
                $exception->getMessage(),
                $exception->getStatusCode(),
                [],
                $exception->getHeaders()
            );
        } catch (Throwable $exception) {
            $this->logException($exception, 500);
            $response = JsonResponse::error('Internal Server Error', 500);
        }

        $this->emitter->emit($response);
    }

    public function middleware(MiddlewareInterface|callable|string $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * @return array<int, MiddlewareInterface|callable|class-string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    private function handleRequest(): Response
    {
        return (new MiddlewarePipeline($this->container))->handle(
            $this->request,
            $this->middleware,
            fn (Request $request): Response => $this->dispatch($request)
        );
    }

    /**
     * @throws ReflectionException
     */
    private function dispatch(Request $request): Response
    {
        $match = $this->router->match($request);
        $request->setRouteParams($match->getParameters());

        [$controllerClass, $methodName] = $match->getHandler();

        if (!class_exists($controllerClass)) {
            throw new HttpError('Endpoint not found', 404);
        }

        $controller = $this->createController($controllerClass, $request);
        $reflectionClass = new ReflectionClass($controller);

        if (!$reflectionClass->hasMethod($methodName)) {
            throw new HttpError('Endpoint not found', 404);
        }

        $method = $reflectionClass->getMethod($methodName);
        $result = $method->invokeArgs(
            $controller,
            $this->resolveMethodArguments($method->getParameters(), $match->getParameters(), $request)
        );

        return $this->applyResponseAttributes(
            $this->normalizeResponse($result),
            $method
        );
    }

    private function createController(string $controllerClass, Request $request): object
    {
        if ($this->container->has($controllerClass)) {
            return $this->container->resolve($controllerClass);
        }

        $controller = $this->container->make($controllerClass);

        if ($controller instanceof Controller) {
            $controller->setContext($request, $this->container);
        }

        return $controller;
    }

    /**
     * @param ReflectionParameter[] $parameters
     */
    private function resolveMethodArguments(array $parameters, array $routeParameters, Request $request): array
    {
        $arguments = [];
        $orderedRouteParameters = array_values($routeParameters);

        foreach ($parameters as $index => $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && $type->getName() === Request::class) {
                $arguments[] = $request;
                continue;
            }

            $bodyDto = $this->getParameterAttribute($parameter, BodyDto::class);
            if ($bodyDto instanceof BodyDto) {
                $dtoClass = $bodyDto->class ?? ($type instanceof ReflectionNamedType ? $type->getName() : null);
                $arguments[] = $this->makeRequestDto($dtoClass, $request);
                continue;
            }

            if (
                $type instanceof ReflectionNamedType
                && !$type->isBuiltin()
                && is_subclass_of($type->getName(), RequestDto::class)
            ) {
                $arguments[] = $this->makeRequestDto($type->getName(), $request);
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
                    $request->query($name, $queryParam->default ?? $this->getDefaultParameterValue($parameter)),
                    $parameter
                );
                continue;
            }

            $body = $this->getParameterAttribute($parameter, Body::class);
            if ($body instanceof Body) {
                $json = $request->getJson();
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

    private function makeRequestDto(?string $dtoClass, Request $request): RequestDto
    {
        if ($dtoClass === null || !is_subclass_of($dtoClass, RequestDto::class)) {
            throw new HttpError('Endpoint not found', 404);
        }

        return $dtoClass::fromRequest($request);
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
        $this->container->singleton(Request::class, $this->request);
        $this->container->singleton('router', $this->router);
        $this->container->singleton(Router::class, $this->router);
        $this->container->singleton(ServiceContainer::class, $this->container);
        $this->container->singleton(DatabaseConfig::class, fn (): DatabaseConfig => DatabaseConfig::fromEnv());
        $this->container->singleton(Database::class, fn (ServiceContainer $container): Database => new Database(
            $container->resolve(DatabaseConfig::class)
        ));
        $this->container->singleton('db', fn (ServiceContainer $container): Database => $container->resolve(Database::class));
        $this->container->singleton(LoggerInterface::class, fn (): LoggerInterface => LoggerFactory::fromEnv());
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

    private function logException(Throwable $exception, int $statusCode): void
    {
        try {
            (new ExceptionLogger($this->container->resolve(LoggerInterface::class)))->log($exception, $this->request, $statusCode);
        } catch (Throwable) {
        }
    }
}
