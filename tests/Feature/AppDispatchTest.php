<?php

use Shift\App;
use Shift\Logging\LoggerInterface;
use Shift\Routing\AttributeRouteLoader;
use Shift\Routing\Router\Router;
use Shift\Service\ServiceContainer;

return [
    'app dispatches route to controller' => function (): void {
        $router = new Router();
        $router->get('/test/api/{argument}', [TestAttributeController::class, 'api']);
        $emitter = new CapturingEmitter();

        $app = new App(makeRequest('GET', '/test/api/demo'), $router, $emitter);
        $app->start();

        $payload = json_decode($emitter->content, true);

        assertSameValue(200, $emitter->statusCode, 'App should emit successful status.');
        assertSameValue('demo', $payload['data']['routeParams']['argument'] ?? null, 'App should pass route params to controller.');
    },

    'app autowires controller constructor dependencies' => function (): void {
        $router = new Router();
        (new AttributeRouteLoader())->load($router, [AutowiredController::class]);
        $emitter = new CapturingEmitter();

        $app = new App(makeRequest('GET', '/autowired/service'), $router, $emitter);
        $app->getContainer()->singleton(AutowiredGreetingService::class, AutowiredGreetingService::class);
        $app->start();

        $payload = json_decode($emitter->content, true);

        assertSameValue(200, $emitter->statusCode, 'Autowired controller should emit successful status.');
        assertSameValue('autowired', $payload['message'] ?? null, 'Controller dependency should be injected from the container.');
        assertSameValue('/autowired/service', $payload['path'] ?? null, 'Autowired controller should retain base controller context.');
    },

    'service container makes classes with typed dependencies' => function (): void {
        $container = new ServiceContainer();
        $container->singleton(AutowiredGreetingService::class, AutowiredGreetingService::class);

        $consumer = $container->make(AutowiredConsumer::class);

        assertSameValue('autowired', $consumer->service->message(), 'Container should autowire typed constructor dependencies.');
    },

    'app binds path and query params from attributes' => function (): void {
        $router = new Router();
        (new AttributeRouteLoader())->load($router, [TestAttributeController::class]);
        $emitter = new CapturingEmitter();

        $app = new App(makeRequest('GET', '/test/api/demo', '', ['include' => 'details']), $router, $emitter);
        $app->start();

        $payload = json_decode($emitter->content, true);

        assertSameValue('demo', $payload['data']['arguments'][0] ?? null, 'PathParam should bind route value.');
        assertSameValue('details', $payload['data']['include'] ?? null, 'QueryParam should bind query value.');
    },

    'app applies status header and body attributes' => function (): void {
        $router = new Router();
        (new AttributeRouteLoader())->load($router, [TestAttributeController::class]);
        $emitter = new CapturingEmitter();

        $app = new App(makeRequest('POST', '/test/created', '{"name":"Shift"}'), $router, $emitter);
        $app->start();

        $payload = json_decode($emitter->content, true);

        assertSameValue(201, $emitter->statusCode, 'Status attribute should override response status.');
        assertArrayHasKeyValue('X-Test', 'created', $emitter->headers, 'Header attribute should add response header.');
        assertSameValue('Shift', $payload['name'] ?? null, 'Body attribute should bind JSON body key.');
    },
    'app logs unhandled exceptions with request context' => function (): void {
        $router = new Router();
        (new AttributeRouteLoader())->load($router, [FailingController::class]);
        $emitter = new CapturingEmitter();
        $logger = new class implements LoggerInterface {
            public array $records = [];

            public function log(string $level, string $message, array $context = []): void
            {
                $this->records[] = compact('level', 'message', 'context');
            }
        };

        $app = new App(makeRequest('GET', '/errors/boom'), $router, $emitter);
        $app->getContainer()->singleton(LoggerInterface::class, $logger);
        $app->start();

        $payload = json_decode($emitter->content, true);
        $record = $logger->records[0] ?? null;

        assertSameValue(500, $emitter->statusCode, 'Unhandled exceptions should emit a 500 response.');
        assertSameValue('Internal Server Error', $payload['error']['message'] ?? null, 'Unhandled exception details should not leak.');
        assertSameValue('error', $record['level'] ?? null, 'Unhandled exceptions should be logged as errors.');
        assertSameValue('Controller exploded', $record['message'] ?? null, 'Log message should contain the exception message.');
        assertSameValue(RuntimeException::class, $record['context']['exception'] ?? null, 'Log context should include the exception class.');
        assertSameValue('/errors/boom', $record['context']['request']['path'] ?? null, 'Log context should include request path.');
    },
];
