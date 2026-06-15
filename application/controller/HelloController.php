<?php

namespace Controllers;

use Engine\Controller;
use Engine\JsonResponse;
use Engine\Routing\Attributes\Get;
use Engine\Routing\Attributes\Body;
use Engine\Routing\Attributes\Header;
use Engine\Routing\Attributes\PathParam;
use Engine\Routing\Attributes\Post;
use Engine\Routing\Attributes\QueryParam;
use Engine\Routing\Attributes\RoutePrefix;
use Engine\Routing\Attributes\Status;

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
    public function api(#[PathParam] ?string $argument = null, #[QueryParam('include')] ?string $include = null): JsonResponse
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
                'include' => $include,
                'routeParams' => $this->request->getRouteParams()
            ]
        ]);
    }

    #[Post('/echo')]
    public function echo(#[Body] array $data): JsonResponse
    {
        return $this->json([
            'data' => $data,
        ]);
    }

    #[Post('/created')]
    #[Status(201)]
    #[Header('X-ShiftPHP-Example', 'created')]
    public function created(#[Body('name')] string $name): array
    {
        return [
            'name' => $name,
            'created' => true,
        ];
    }
}
