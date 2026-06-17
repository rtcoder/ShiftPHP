<?php

namespace Shift\OpenApi;

final class OpenApiValidator
{
    /**
     * @return list<string>
     */
    public function validate(array $document): array
    {
        $errors = [];

        if (($document['openapi'] ?? null) !== '3.0.3') {
            $errors[] = 'Document must use OpenAPI 3.0.3.';
        }

        if (!is_array($document['info'] ?? null) || !is_string($document['info']['title'] ?? null)) {
            $errors[] = 'Document info.title is required.';
        }

        if (!is_array($document['paths'] ?? null)) {
            $errors[] = 'Document paths object is required.';

            return $errors;
        }

        $operationIds = [];

        foreach ($document['paths'] as $path => $operations) {
            if (!is_string($path) || !str_starts_with($path, '/')) {
                $errors[] = 'Path must start with /: ' . (string) $path;
                continue;
            }

            if (!is_array($operations) || $operations === []) {
                $errors[] = 'Path has no operations: ' . $path;
                continue;
            }

            foreach ($operations as $method => $operation) {
                if (!is_array($operation)) {
                    $errors[] = strtoupper((string) $method) . ' ' . $path . ' operation must be an object.';
                    continue;
                }

                $operationId = $operation['operationId'] ?? null;

                if (!is_string($operationId) || $operationId === '') {
                    $errors[] = strtoupper((string) $method) . ' ' . $path . ' operationId is required.';
                } elseif (isset($operationIds[$operationId])) {
                    $errors[] = 'Duplicate operationId: ' . $operationId;
                } else {
                    $operationIds[$operationId] = true;
                }

                if (!is_array($operation['responses'] ?? null) || $operation['responses'] === []) {
                    $errors[] = strtoupper((string) $method) . ' ' . $path . ' must define at least one response.';
                }

                foreach ($this->pathParameters($path) as $parameter) {
                    if (!$this->hasPathParameter($operation['parameters'] ?? [], $parameter)) {
                        $errors[] = strtoupper((string) $method) . ' ' . $path . ' is missing path parameter ' . $parameter . '.';
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function pathParameters(string $path): array
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)}/', $path, $matches);

        return $matches[1] ?? [];
    }

    private function hasPathParameter(mixed $parameters, string $name): bool
    {
        if (!is_array($parameters)) {
            return false;
        }

        foreach ($parameters as $parameter) {
            if (($parameter['name'] ?? null) === $name && ($parameter['in'] ?? null) === 'path') {
                return true;
            }
        }

        return false;
    }
}
