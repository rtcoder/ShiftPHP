<?php

namespace Controllers;

use Engine\Controller;
use Engine\JsonResponse;
use Engine\Request;
use Engine\Routing\Attributes\Get;
use Engine\Routing\Attributes\Post;
use Engine\Routing\Attributes\RoutePrefix;

/**
 * Class HelloController
 * @package Controllers
 */
#[RoutePrefix('/hello')]
class HelloController extends Controller
{
    #[Get('')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Hello from ShiftPHP!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    #[Get('/about')]
    public function about(): JsonResponse
    {
        return $this->json([
            'title' => 'About ShiftPHP',
            'version' => '1.0.0'
        ]);
    }

    #[Get('/api')]
    #[Get('/api/{argument}')]
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

    #[Post('/echo')]
    public function echo(Request $request): JsonResponse
    {
        return $this->json([
            'data' => $request->getJson(),
        ]);
    }
}
