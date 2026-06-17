<?php

namespace Shift;

use Shift\Response\JsonResponse;
use Shift\Response\Response;
use Shift\Service\ServiceContainer;
use JsonException;

/**
 * Class Controller
 * @package Shift
 */
abstract class Controller
{
    protected Request $request;
    protected ServiceContainer $container;

    public function __construct(Request $request, ?ServiceContainer $container = null)
    {
        $this->setContext($request, $container ?? new ServiceContainer());
    }

    public function setContext(Request $request, ServiceContainer $container): void
    {
        $this->request = $request;
        $this->container = $container;
    }

    /**
     * @param array $data
     * @param int $statusCode
     * @return JsonResponse
     * @throws JsonException
     */
    protected function json(array $data, int $statusCode = 200): JsonResponse
    {
        return new JsonResponse($data, $statusCode);
    }

    protected function noContent(): Response
    {
        return new Response('', 204);
    }

    protected function error(string $message, int $statusCode = 400, array $context = [], array $headers = []): JsonResponse
    {
        return JsonResponse::error($message, $statusCode, $context, $headers);
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
