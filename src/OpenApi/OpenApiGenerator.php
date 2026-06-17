<?php

namespace Shift\OpenApi;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Shift\OpenApi\Attributes\Deprecated;
use Shift\OpenApi\Attributes\Description;
use Shift\OpenApi\Attributes\Response as OpenApiResponse;
use Shift\OpenApi\Attributes\Schema;
use Shift\OpenApi\Attributes\Security;
use Shift\OpenApi\Attributes\Summary;
use Shift\OpenApi\Attributes\Tag;
use Shift\Response\JsonResponse;
use Shift\Response\Response as HttpResponse;
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
            'tags' => $this->tags($controller, $method),
            'responses' => $this->responses($method, $statusCode),
        ];

        $summary = $this->summary($method);

        if ($summary !== null) {
            $operation['summary'] = $summary;
        }

        $description = $this->description($method);

        if ($description !== null) {
            $operation['description'] = $description;
        }

        if ($method->getAttributes(Deprecated::class) !== []) {
            $operation['deprecated'] = true;
        }

        $security = $this->security($controller, $method);

        if ($security !== []) {
            $operation['security'] = $security;
        }

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

    private function responses(ReflectionMethod $method, int $defaultStatusCode): array
    {
        $responses = [];

        foreach ($method->getAttributes(OpenApiResponse::class) as $attribute) {
            /** @var OpenApiResponse $response */
            $response = $attribute->newInstance();
            $responses[(string) $response->status] = $this->response($method, $response->status, $response->description, $response->type);
        }

        if ($responses === []) {
            $responses[(string) $defaultStatusCode] = $this->response($method, $defaultStatusCode);
        }

        ksort($responses);

        return $responses;
    }

    private function response(ReflectionMethod $method, int $statusCode, ?string $description = null, ?string $type = null): array
    {
        $response = [
            'description' => $description ?? $this->responseDescription($statusCode),
        ];

        $headers = $this->responseHeaders($method);

        if ($headers !== []) {
            $response['headers'] = $headers;
        }

        if ($statusCode !== 204 && ($type !== null || $this->returnsJson($method))) {
            $response['content'] = [
                'application/json' => [
                    'schema' => $this->schemaForPhpType($type ?? 'object'),
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
                $parameters[] = $this->parameter($queryParameter, 'query', $parameter, $this->isRequiredParameter($parameter));
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

            if ($this->isRequiredParameter($parameter)) {
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
                $property = $this->dtoProperty($class, (string) $field);
                $schema['properties'][$field] = $this->schemaForRules($rules, $property);

                if ($this->rulesRequireField($rules, $property)) {
                    $required[] = $field;
                }
            }
        }

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    private function schemaForRules(mixed $rules, ?ReflectionProperty $property = null): array
    {
        $normalizedRules = $this->normalizeRules($rules);
        $schemaAttribute = $this->schemaAttribute($property);

        if ($schemaAttribute?->type !== null) {
            $schema = $this->schemaForPhpType($schemaAttribute->type);
        } elseif ($property !== null && $property->getType() instanceof ReflectionNamedType) {
            $schema = $this->schemaForPhpType($property->getType()->getName());
        } elseif (in_array('integer', $normalizedRules, true) || in_array('int', $normalizedRules, true)) {
            $schema = ['type' => 'integer'];
        } elseif (in_array('numeric', $normalizedRules, true) || in_array('float', $normalizedRules, true)) {
            $schema = ['type' => 'number'];
        } elseif (in_array('boolean', $normalizedRules, true) || in_array('bool', $normalizedRules, true)) {
            $schema = ['type' => 'boolean'];
        } elseif (in_array('array', $normalizedRules, true)) {
            $schema = ['type' => 'array', 'items' => ['type' => $schemaAttribute?->itemsType ?? 'string']];
        } else {
            $schema = ['type' => 'string'];
        }

        if (in_array('email', $normalizedRules, true)) {
            $schema['format'] = 'email';
        }

        if (in_array('date', $normalizedRules, true)) {
            $schema['format'] = 'date';
        }

        if (in_array('datetime', $normalizedRules, true) || in_array('date_time', $normalizedRules, true)) {
            $schema['format'] = 'date-time';
        }

        return $this->applySchemaAttribute($schema, $schemaAttribute);
    }

    private function rulesRequireField(mixed $rules, ?ReflectionProperty $property = null): bool
    {
        $schemaAttribute = $this->schemaAttribute($property);

        if ($schemaAttribute?->required !== null) {
            return $schemaAttribute->required;
        }

        return in_array('required', $this->normalizeRules($rules), true);
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
        ] + $this->descriptionFragment($parameter);
    }

    private function schemaForParameter(ReflectionParameter $parameter): array
    {
        $schemaAttribute = $this->schemaAttribute($parameter);

        if ($schemaAttribute?->type !== null) {
            return $this->applySchemaAttribute($this->schemaForPhpType($schemaAttribute->type), $schemaAttribute);
        }

        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType) {
            return $this->applySchemaAttribute(['type' => 'string'], $schemaAttribute);
        }

        $schema = $this->schemaForPhpType($type->getName());

        if ($parameter->allowsNull()) {
            $schema['nullable'] = true;
        }

        return $this->applySchemaAttribute($schema, $schemaAttribute);
    }

    private function schemaForPhpType(string $type): array
    {
        return match (ltrim($type, '\\')) {
            'int', 'integer' => ['type' => 'integer'],
            'float', 'number' => ['type' => 'number'],
            'bool', 'boolean' => ['type' => 'boolean'],
            'string' => ['type' => 'string'],
            'object' => ['type' => 'object'],
            'array' => ['type' => 'array', 'items' => ['type' => 'string']],
            'DateTimeInterface', 'DateTimeImmutable', 'DateTime' => ['type' => 'string', 'format' => 'date-time'],
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
            || $name !== HttpResponse::class;
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

    /**
     * @return list<string>
     */
    private function tags(ReflectionClass $controller, ReflectionMethod $method): array
    {
        $tags = [];

        foreach ([$controller, $method] as $reflection) {
            foreach ($reflection->getAttributes(Tag::class) as $attribute) {
                /** @var Tag $tag */
                $tag = $attribute->newInstance();
                $tags[] = $tag->name;
            }
        }

        return array_values(array_unique($tags !== [] ? $tags : [$this->tag($controller)]));
    }

    /**
     * @return list<array<string, list<string>>>
     */
    private function security(ReflectionClass $controller, ReflectionMethod $method): array
    {
        $security = [];

        foreach ([$controller, $method] as $reflection) {
            foreach ($reflection->getAttributes(Security::class) as $attribute) {
                /** @var Security $item */
                $item = $attribute->newInstance();
                $security[] = [$item->name => $item->scopes];
            }
        }

        return $security;
    }

    private function summary(ReflectionMethod $method): ?string
    {
        $attributes = $method->getAttributes(Summary::class);

        if ($attributes === []) {
            return null;
        }

        /** @var Summary $summary */
        $summary = $attributes[0]->newInstance();

        return $summary->text;
    }

    private function description(ReflectionMethod $method): ?string
    {
        $attributes = $method->getAttributes(Description::class);

        if ($attributes === []) {
            return null;
        }

        /** @var Description $description */
        $description = $attributes[0]->newInstance();

        return $description->text;
    }

    private function descriptionFragment(ReflectionParameter $parameter): array
    {
        $attributes = $parameter->getAttributes(Description::class);

        if ($attributes === []) {
            return [];
        }

        /** @var Description $description */
        $description = $attributes[0]->newInstance();

        return ['description' => $description->text];
    }

    private function isRequiredParameter(ReflectionParameter $parameter): bool
    {
        $schemaAttribute = $this->schemaAttribute($parameter);

        if ($schemaAttribute?->required !== null) {
            return $schemaAttribute->required;
        }

        return !$parameter->allowsNull() && !$parameter->isDefaultValueAvailable();
    }

    private function schemaAttribute(ReflectionParameter|ReflectionProperty|null $reflection): ?Schema
    {
        if ($reflection === null) {
            return null;
        }

        $attributes = $reflection->getAttributes(Schema::class);

        if ($attributes === []) {
            return null;
        }

        /** @var Schema $schema */
        $schema = $attributes[0]->newInstance();

        return $schema;
    }

    private function applySchemaAttribute(array $schema, ?Schema $attribute): array
    {
        if ($attribute === null) {
            return $schema;
        }

        if ($attribute->format !== null) {
            $schema['format'] = $attribute->format;
        }

        if ($attribute->description !== null) {
            $schema['description'] = $attribute->description;
        }

        if ($attribute->itemsType !== null && ($schema['type'] ?? null) === 'array') {
            $schema['items'] = ['type' => $attribute->itemsType];
        }

        if ($attribute->enum !== []) {
            $schema['enum'] = $attribute->enum;
        }

        if ($attribute->nullable !== null) {
            $schema['nullable'] = $attribute->nullable;
        }

        return $schema;
    }

    private function dtoProperty(string $class, string $field): ?ReflectionProperty
    {
        if (!class_exists($class)) {
            return null;
        }

        $reflection = new ReflectionClass($class);

        return $reflection->hasProperty($field) ? $reflection->getProperty($field) : null;
    }

    /**
     * @return list<string>
     */
    private function normalizeRules(mixed $rules): array
    {
        $rules = is_array($rules) ? $rules : explode('|', (string) $rules);

        return array_map(static fn (string $rule): string => strtolower(strtok($rule, ':') ?: $rule), $rules);
    }
}
