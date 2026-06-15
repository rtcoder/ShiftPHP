<?php

namespace Controllers;

use Engine\JsonResponse;
use Engine\Request;

/**
 * Class HelloController
 * @package Controllers
 */
class HelloController extends \Engine\Controller
{
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Hello from ShiftPHP!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function about(): JsonResponse
    {
        return $this->json([
            'title' => 'About ShiftPHP',
            'version' => '1.0.0'
        ]);
    }

    public function api(?string $argument = null): JsonResponse
    {
        $arguments = [];
        if ($argument !== null) {
            $arguments[] = $argument;
        }

        return $this->json([
            'status' => 'success',
            'message' => 'API endpoint working!',
            'data' => [
                'path' => $this->request->getPath(),
                'method' => $this->request->getMethod(),
                'arguments' => $arguments,
                'routeParams' => $this->request->getRouteParams()
            ]
        ]);
    }

    public function echo(Request $request): JsonResponse
    {
        return $this->json([
            'data' => $request->getJson(),
        ]);
    }
}
