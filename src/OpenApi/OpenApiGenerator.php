<?php

namespace Shift\OpenApi;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Shift\Response\JsonResponse;
use Shift\Response\Response;
use Shift\Routing\Attributes\Body;
use Shift\Routing\Attributes\BodyDto;
use Shift\Routing\Attributes\Header;
use Shift\Routing\Attributes\PathParam;
use Shift\Routing\Attributes\QueryParam;
use Shift\Routing\Attributes\Status;
use Shift\Routing\Router\Route;
use Shift\Routing\Router\Router;
use Shift\Validation\RequestDto;

final class OpenApiGenerator
{
    public function generate(Router $router): array
    {
        $paths = [];

        foreach ($router->getRoutes() as $route) {
            $path = $this->openApiPath($route->getPath());
            $method = strtolower($route->getMethod());

            $paths[$path][$method] = $this->operation($route);
        }

        ksort($paths);

        foreach ($paths as $path => $operations) {
            ksort($operations);
            $paths[$path] = $operations;
        }

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'ShiftPHP API',
                'version' => getenv('APP_VERSION') ?: '0.1.0',
            ],
            'paths' => $paths,
        ];
    }

    private function operation(Route $route): array
    {
        [$controllerClass, $methodName] = $route->getHandler();
        $method = new ReflectionMethod($controllerClass, $methodName);
        $controller = new ReflectionClass($controllerClass);
        $statusCode = $this->statusCode($method);
        $operation = [
            'operationId' => $this->operationId($controller, $method),
            'tags' => [$this->tag($controller)],
            'responses' => [
                (string) $statusCode => $this->response($method, $statusCode),
            ],
        ];

        $parameters = $this->parameters($route, $method);

        if ($parameters !== []) {
            $operation['parameters'] = $parameters;
        }

        $requestBody = $this->requestBody($method);

        if ($requestBody !== null) {
            $operation['requestBody'] = $requestBody;
        }

        return $operation;
    }

    private function response(ReflectionMethod $method, int $statusCode): array
    {
        $response = [
            'description' => $this->responseDescription($statusCode),
        ];

        $headers = $this->responseHeaders($method);

        if ($headers !== []) {
            $response['headers'] = $headers;
        }

        if ($statusCode !== 204 && $this->returnsJson($method)) {
            $response['content'] = [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                    ],
                ],
            ];
        }

        return $response;
    }

    private function responseHeaders(ReflectionMethod $method): array
    {
        $headers = [];

        foreach ($method->getAttributes(Header::class) as $attribute) {
            /** @var Header $header */
            $header = $attribute->newInstance();
            $headers[$header->name] = [
                'description' => $header->value,
                'schema' => [
                    'type' => 'string',
                    'example' => $header->value,
                ],
            ];
        }

        ksort($headers);

        return $headers;
    }

    private function parameters(Route $route, ReflectionMethod $method): array
    {
        $parameters = [];
        $pathParameterNames = $this->pathParameterNames($route->getPath());
        $usedPathParameters = [];

        foreach ($method->getParameters() as $parameter) {
            $pathParameter = $this->pathParameterName($parameter);

            if ($pathParameter !== null || in_array($parameter->getName(), $pathParameterNames, true)) {
                $name = $pathParameter ?? $parameter->getName();
                $parameters[] = $this->parameter($name, 'path', $parameter, true);
                $usedPathParameters[] = $name;
                continue;
            }

            $queryParameter = $this->queryParameterName($parameter);

            if ($queryParameter !== null) {
                $parameters[] = $this->parameter($queryParameter, 'query', $parameter, !$parameter->allowsNull() && !$parameter->isDefaultValueAvailable());
            }
        }

        foreach (array_diff($pathParameterNames, $usedPathParameters) as $name) {
            $parameters[] = [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                ],
            ];
        }

        usort($parameters, static function (array $left, array $right): int {
            return [$left['in'], $left['name']] <=> [$right['in'], $right['name']];
        });

        return $parameters;
    }

    private function requestBody(ReflectionMethod $method): ?array
    {
        $properties = [];
        $required = [];

        foreach ($method->getParameters() as $parameter) {
            $bodyDto = $this->bodyDtoClass($parameter);

            if ($bodyDto !== null) {
                return [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => $this->schemaForDto($bodyDto),
                        ],
                    ],
                ];
            }

            $bodyKey = $this->bodyKey($parameter);

            if ($bodyKey === null) {
                continue;
            }

            $properties[$bodyKey] = $this->schemaForParameter($parameter);

            if (!$parameter->allowsNull() && !$parameter->isDefaultValueAvailable()) {
                $required[] = $bodyKey;
            }
        }

        if ($properties === []) {
            return null;
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => $schema,
                ],
            ],
        ];
    }

    private function schemaForDto(string $class): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
        ];
        $required = [];

        if (is_subclass_of($class, RequestDto::class)) {
            foreach ($class::rules() as $field => $rules) {
                $schema['properties'][$field] = $this->schemaForRules($rules);

                if ($this->rulesRequireField($rules)) {
                    $required[] = $field;
                }
            }
        }

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    private function schemaForRules(mixed $rules): array
    {
        $rules = is_array($rules) ? $rules : explode('|', (string) $rules);
        $rules = array_map(static fn (string $rule): string => strtolower(strtok($rule, ':') ?: $rule), $rules);

        if (in_array('integer', $rules, true) || in_array('int', $rules, true)) {
            return ['type' => 'integer'];
        }

        if (in_array('numeric', $rules, true) || in_array('float', $rules, true)) {
            return ['type' => 'number'];
        }

        if (in_array('boolean', $rules, true) || in_array('bool', $rules, true)) {
            return ['type' => 'boolean'];
        }

        if (in_array('array', $rules, true)) {
            return ['type' => 'array', 'items' => ['type' => 'string']];
        }

        return ['type' => 'string'];
    }

    private function rulesRequireField(mixed $rules): bool
    {
        $rules = is_array($rules) ? $rules : explode('|', (string) $rules);

        return in_array('required', array_map('strtolower', $rules), true);
    }

    private function bodyDtoClass(ReflectionParameter $parameter): ?string
    {
        $attributes = $parameter->getAttributes(BodyDto::class);

        if ($attributes !== []) {
            /** @var BodyDto $bodyDto */
            $bodyDto = $attributes[0]->newInstance();

            if (is_string($bodyDto->class) && $bodyDto->class !== '') {
                return $bodyDto->class;
            }
        }

        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && is_subclass_of($type->getName(), RequestDto::class)) {
            return $type->getName();
        }

        return null;
    }

    private function bodyKey(ReflectionParameter $parameter): ?string
    {
        $attributes = $parameter->getAttributes(Body::class);

        if ($attributes === []) {
            return null;
        }

        /** @var Body $body */
        $body = $attributes[0]->newInstance();

        return $body->key ?? $parameter->getName();
    }

    private function pathParameterName(ReflectionParameter $parameter): ?string
    {
        $attributes = $parameter->getAttributes(PathParam::class);

        if ($attributes === []) {
            return null;
        }

        /** @var PathParam $path */
        $path = $attributes[0]->newInstance();

        return $path->name ?? $parameter->getName();
    }

    private function queryParameterName(ReflectionParameter $parameter): ?string
    {
        $attributes = $parameter->getAttributes(QueryParam::class);

        if ($attributes === []) {
            return null;
        }

        /** @var QueryParam $query */
        $query = $attributes[0]->newInstance();

        return $query->name ?? $parameter->getName();
    }

    private function parameter(string $name, string $in, ReflectionParameter $parameter, bool $required): array
    {
        return [
            'name' => $name,
            'in' => $in,
            'required' => $required,
            'schema' => $this->schemaForParameter($parameter),
        ];
    }

    private function schemaForParameter(ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType) {
            return ['type' => 'string'];
        }

        return $this->schemaForPhpType($type->getName());
    }

    private function schemaForPhpType(string $type): array
    {
        return match (ltrim($type, '\\')) {
            'int' => ['type' => 'integer'],
            'float' => ['type' => 'number'],
            'bool' => ['type' => 'boolean'],
            'array' => ['type' => 'array', 'items' => ['type' => 'string']],
            default => ['type' => 'string'],
        };
    }

    private function statusCode(ReflectionMethod $method): int
    {
        $attributes = $method->getAttributes(Status::class);

        if ($attributes === []) {
            return 200;
        }

        /** @var Status $status */
        $status = $attributes[0]->newInstance();

        return $status->code;
    }

    private function returnsJson(ReflectionMethod $method): bool
    {
        $type = $method->getReturnType();

        if (!$type instanceof ReflectionNamedType) {
            return true;
        }

        $name = ltrim($type->getName(), '\\');

        return $name === 'array'
            || $name === JsonResponse::class
            || is_subclass_of($name, JsonResponse::class)
            || $name !== Response::class;
    }

    private function responseDescription(int $statusCode): string
    {
        return match ($statusCode) {
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            default => 'Response',
        };
    }

    /**
     * @return list<string>
     */
    private function pathParameterNames(string $path): array
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)}/', $path, $matches);

        return $matches[1] ?? [];
    }

    private function openApiPath(string $path): string
    {
        return preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)}/', '{$1}', $path) ?? $path;
    }

    private function operationId(ReflectionClass $controller, ReflectionMethod $method): string
    {
        return lcfirst($controller->getShortName()) . ucfirst($method->getName());
    }

    private function tag(ReflectionClass $controller): string
    {
        return preg_replace('/Controller$/', '', $controller->getShortName()) ?: $controller->getShortName();
    }
}
