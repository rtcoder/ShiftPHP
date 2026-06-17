<?php

use Shift\App;
use Shift\Middleware\AuthMiddleware;
use Shift\Middleware\AuthorizationMiddleware;
use Shift\Middleware\CorsMiddleware;
use Shift\Request;
use Shift\Response\JsonResponse;
use Shift\Response\Response;
use Shift\Routing\AttributeRouteLoader;
use Shift\Routing\Router\Router;

return [
    'app runs middleware around controller dispatch' => function (): void {
        $router = new Router();
        $router->get('/test/api/{argument}', [TestAttributeController::class, 'api']);
        $emitter = new CapturingEmitter();
        $events = [];

        $app = new App(makeRequest('GET', '/test/api/demo'), $router, $emitter);
        $app->middleware(function (Request $request, callable $next) use (&$events): Response {
            $events[] = 'before';
            $response = $next($request);
            $events[] = 'after';

            return new Response(
                $response->getContent(),
                $response->getStatusCode(),
                $response->getHeaders() + ['X-Middleware' => 'callable']
            );
        });
        $app->start();

        assertSameValue(['before', 'after'], $events, 'Middleware should wrap controller dispatch.');
        assertArrayHasKeyValue('X-Middleware', 'callable', $emitter->headers, 'Middleware should be able to modify response headers.');
    },

    'app supports middleware classes' => function (): void {
        $router = new Router();
        $router->get('/test/api/{argument}', [TestAttributeController::class, 'api']);
        $emitter = new CapturingEmitter();

        $app = new App(makeRequest('GET', '/test/api/demo'), $router, $emitter);
        $app->middleware(HeaderMiddleware::class);
        $app->start();

        assertArrayHasKeyValue('X-Middleware', 'class', $emitter->headers, 'Middleware class should be resolved and executed.');
    },

    'middleware can short-circuit request handling' => function (): void {
        $router = new Router();
        $router->get('/test/api/{argument}', [TestAttributeController::class, 'api']);
        $emitter = new CapturingEmitter();

        $app = new App(makeRequest('GET', '/test/api/demo'), $router, $emitter);
        $app->middleware(static fn (Request $request, callable $next): Response => JsonResponse::error('Blocked', 403));
        $app->start();

        $payload = json_decode($emitter->content, true);

        assertSameValue(403, $emitter->statusCode, 'Middleware should be able to short-circuit the request.');
        assertSameValue('Blocked', $payload['error']['message'] ?? null, 'Short-circuit response should be emitted.');
    },

    'cors middleware handles preflight requests' => function (): void {
        $router = new Router();
        $emitter = new CapturingEmitter();

        $app = new App(makeRequest('OPTIONS', '/anything'), $router, $emitter);
        $app->middleware(new CorsMiddleware());
        $app->start();

        assertSameValue(204, $emitter->statusCode, 'CORS preflight should short-circuit with 204.');
        assertArrayHasKeyValue('Access-Control-Allow-Origin', '*', $emitter->headers, 'CORS should expose allowed origin.');
    },

    'auth middleware authenticates request user' => function (): void {
        $router = new Router();
        (new AttributeRouteLoader())->load($router, [AuthenticatedController::class]);
        $emitter = new CapturingEmitter();

        $app = new App(makeRequest('GET', '/auth/me'), $router, $emitter);
        $app->middleware(new AuthMiddleware(new HeaderAuthenticator()));
        $app->middleware(new AuthorizationMiddleware(new AllowAuthorizer(), 'view'));
        $app->start();

        $payload = json_decode($emitter->content, true);

        assertSameValue(200, $emitter->statusCode, 'Authenticated request should continue.');
        assertSameValue('user-1', $payload['id'] ?? null, 'Authenticated user should be stored on the request.');
    },

    'auth middleware rejects unauthenticated requests' => function (): void {
        $router = new Router();
        (new AttributeRouteLoader())->load($router, [AuthenticatedController::class]);
        $emitter = new CapturingEmitter();

        $app = new App(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/auth/me']), $router, $emitter);
        $app->middleware(new AuthMiddleware(new HeaderAuthenticator()));
        $app->start();

        assertSameValue(401, $emitter->statusCode, 'Unauthenticated request should return 401.');
    },
];
