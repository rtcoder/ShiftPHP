<?php

use Modules\Health\Services\HealthService;
use Shift\App;
use Shift\Modules\ModuleLoader;
use Shift\Routing\Router\Route;
use Shift\Routing\Router\Router;
use Shift\Service\ServiceContainer;

return [
    'module loader registers services and routes' => function (): void {
        $loader = (new ModuleLoader())->load();
        $router = new Router();
        $container = new ServiceContainer();

        $loader->registerServices($container);
        $loader->registerRoutes($router);
        $loader->boot($container);

        assertSameValue(true, $container->has(HealthService::class), 'Health module service should be registered.');
        assertSameValue(true, $container->resolve('health.booted'), 'Health module boot hook should run.');
        assertSameValue(true, $loader->getConfig('health')['enabled'] ?? null, 'Health module config file should load.');
        assertSameValue('health', $loader->getConfig('health')['module'] ?? null, 'Health module config method should merge.');

        $routes = array_map(
            static fn (Route $route): string => $route->getMethod() . ' ' . $route->getPath(),
            $router->getRoutes()
        );

        assertSameValue(['GET /health'], $routes, 'Health module route should be registered.');
    },

    'app dispatches module controller with service' => function (): void {
        $loader = (new ModuleLoader())->load();
        $router = new Router();
        $emitter = new CapturingEmitter();
        $app = new App(makeRequest('GET', '/health'), $router, $emitter);

        $loader->registerServices($app->getContainer());
        $loader->registerRoutes($router);
        $app->start();

        $payload = json_decode($emitter->content, true);

        assertSameValue(200, $emitter->statusCode, 'Module route should emit successful status.');
        assertSameValue('health', $payload['module'] ?? null, 'Module controller should resolve its service.');
    },
];
