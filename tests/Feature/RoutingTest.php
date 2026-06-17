<?php

use Shift\Error\HttpError;
use Shift\Routing\AttributeRouteLoader;
use Shift\Routing\Router\Route;
use Shift\Routing\Router\Router;

return [
    'router matches route params' => function (): void {
        $router = new Router();
        $router->get('/test/api/{argument}', [TestAttributeController::class, 'api']);

        $match = $router->match(makeRequest('GET', '/test/api/example'));

        assertSameValue(['argument' => 'example'], $match->getParameters(), 'Route parameters should be extracted.');
    },

    'attribute loader registers controller routes' => function (): void {
        $router = new Router();
        (new AttributeRouteLoader())->load($router, [TestAttributeController::class]);

        $routes = array_map(
            static fn (Route $route): string => $route->getMethod() . ' ' . $route->getPath(),
            $router->getRoutes()
        );

        assertSameValue(
            [
                'GET /test/api/{argument}',
                'POST /test/created',
            ],
            $routes,
            'Attribute loader should register all test controller routes.'
        );
    },

    'router returns 405 with Allow header' => function (): void {
        $router = new Router();
        $router->get('/test/api/{argument}', [TestAttributeController::class, 'api']);

        try {
            $router->match(makeRequest('POST', '/test/api/example'));
        } catch (HttpError $error) {
            assertSameValue(405, $error->getStatusCode(), 'Wrong method should return 405.');
            assertArrayHasKeyValue('Allow', 'GET', $error->getHeaders(), '405 should expose allowed methods.');
            return;
        }

        throw new RuntimeException('Expected HttpError was not thrown.');
    },
];
