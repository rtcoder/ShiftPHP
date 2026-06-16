<?php

namespace Engine\Response;

use JsonException;

class JsonResponse extends Response
{
    /**
     * @throws JsonException
     */
    public function __construct(array $data = [], int $statusCode = 200, array $headers = [])
    {
        parent::__construct(
            json_encode($data, JSON_THROW_ON_ERROR),
            $statusCode,
            ['Content-Type' => 'application/json'] + $headers
        );
    }

    public static function ok(array $data = []): self
    {
        return new self($data);
    }

    /**
     * @throws JsonException
     */
    public static function created(array $data = []): self
    {
        return new self($data, 201);
    }

    public static function error(string $message, int $statusCode = 400, array $context = [], array $headers = []): self
    {
        $payload = [
            'error' => [
                'message' => $message,
                'status' => $statusCode,
            ],
        ];

        if ($context !== []) {
            $payload['error']['context'] = $context;
        }

        return new self($payload, $statusCode, $headers);
    }
}
