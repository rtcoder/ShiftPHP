<?php

namespace Engine;

/**
 * Class Controller
 * @package Engine
 */
abstract class Controller
{
    protected Request $request;
    protected ServiceContainer $container;

    public function __construct(Request $request, ?ServiceContainer $container = null)
    {
        $this->request = $request;
        $this->container = $container ?? new ServiceContainer();
    }

    /**
     * @param array $data
     * @return void
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    }

    protected function noContent(): void
    {
        http_response_code(204);
    }

    protected function error(string $message, int $statusCode = 400, array $context = []): void
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

        $this->json($payload, $statusCode);
    }

    /**
     * @param string $url
     * @return never
     */
    protected function redirect(string $url): never
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * @return Request
     */
    protected function getRequest(): Request
    {
        return $this->request;
    }

    protected function getContainer(): ServiceContainer
    {
        return $this->container;
    }
}
