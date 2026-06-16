<?php

use Engine\App;
use Engine\Controller;
use Engine\Error\HttpError;
use Engine\Modules\ModuleLoader;
use Engine\Request;
use Engine\Response\JsonResponse;
use Engine\Response\ResponseEmitter;
use Engine\Router;
use Engine\Routing\AttributeRouteLoader;
use Engine\Routing\Attributes\Body;
use Engine\Routing\Attributes\Get;
use Engine\Routing\Attributes\Header;
use Engine\Routing\Attributes\PathParam;
use Engine\Routing\Attributes\Post;
use Engine\Routing\Attributes\QueryParam;
use Engine\Routing\Attributes\RoutePrefix;
use Engine\Routing\Attributes\Status;
use Engine\ServiceContainer;
use Modules\Health\Services\HealthService;

require_once __DIR__ . '/../bootstrap.php';

final class CapturingEmitter extends ResponseEmitter
{
    public int $statusCode = 0;
    public array $headers = [];
    public string $content = '';

    public function emit(\Engine\Response\Response $response): void
    {
        $this->statusCode = $response->getStatusCode();
        $this->headers = $response->getHeaders();
        $this->content = $response->getContent();
    }
}

#[RoutePrefix('/test')]
final class TestAttributeController extends Controller
{
    #[Get('/api/{argument}')]
    public function api(#[PathParam] ?string $argument = null, #[QueryParam('include')] ?string $include = null): JsonResponse
    {
        $arguments = [];
        if ($argument !== null) {
            $arguments[] = $argument;
        }

        return $this->json([
            'data' => [
                'arguments' => $arguments,
                'include' => $include,
                'routeParams' => $this->request->getRouteParams(),
            ],
        ]);
    }

    #[Post('/created')]
    #[Status(201)]
    #[Header('X-Test', 'created')]
    public function created(#[Body('name')] string $name): array
    {
        return [
            'name' => $name,
            'created' => true,
        ];
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

function assertArrayHasKeyValue(string $key, mixed $expected, array $actual, string $message): void
{
    if (!array_key_exists($key, $actual) || $actual[$key] !== $expected) {
        throw new RuntimeException($message . "\nArray: " . var_export($actual, true));
    }
}

function makeRequest(string $method, string $uri, string $body = '', array $query = []): Request
{
    return new Request(
        [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri,
            'HTTP_AUTHORIZATION' => 'Bearer token',
        ],
        $query,
        [],
        $body
    );
}

$tests = [];

$tests['router matches route params'] = function (): void {
    $router = new Router();
    $router->get('/test/api/{argument}', [TestAttributeController::class, 'api']);

    $match = $router->match(makeRequest('GET', '/test/api/example'));

    assertSameValue(['argument' => 'example'], $match->getParameters(), 'Route parameters should be extracted.');
};

$tests['attribute loader registers controller routes'] = function (): void {
    $router = new Router();
    (new AttributeRouteLoader())->load($router, [TestAttributeController::class]);

    $routes = array_map(
        static fn (\Engine\Route $route): string => $route->getMethod() . ' ' . $route->getPath(),
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
};

$tests['router returns 405 with Allow header'] = function (): void {
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
};

$tests['request parses json and headers'] = function (): void {
    $request = makeRequest('POST', '/test/created', '{"name":"Shift"}');

    assertSameValue(['name' => 'Shift'], $request->getJson(), 'JSON body should parse.');
    assertSameValue('Shift', $request->input('name'), 'Input should read JSON body.');
    assertSameValue('Bearer token', $request->getHeader('Authorization'), 'Header should be available.');
};

$tests['request rejects malformed json'] = function (): void {
    $request = makeRequest('POST', '/test/created', '{bad');

    try {
        $request->getJson();
    } catch (HttpError $error) {
        assertSameValue(400, $error->getStatusCode(), 'Malformed JSON should return 400.');
        return;
    }

    throw new RuntimeException('Expected malformed JSON error was not thrown.');
};

$tests['json response encodes payload'] = function (): void {
    $response = JsonResponse::ok(['status' => 'ok']);

    assertSameValue(200, $response->getStatusCode(), 'JSON response should default to 200.');
    assertArrayHasKeyValue('Content-Type', 'application/json', $response->getHeaders(), 'JSON response should set content type.');
    assertSameValue('{"status":"ok"}', $response->getContent(), 'JSON response should encode payload.');
};

$tests['app dispatches route to controller'] = function (): void {
    $router = new Router();
    $router->get('/test/api/{argument}', [TestAttributeController::class, 'api']);
    $emitter = new CapturingEmitter();

    $app = new App(makeRequest('GET', '/test/api/demo'), $router, $emitter);
    $app->start();

    $payload = json_decode($emitter->content, true);

    assertSameValue(200, $emitter->statusCode, 'App should emit successful status.');
    assertSameValue('demo', $payload['data']['routeParams']['argument'] ?? null, 'App should pass route params to controller.');
};

$tests['app binds path and query params from attributes'] = function (): void {
    $router = new Router();
    (new AttributeRouteLoader())->load($router, [TestAttributeController::class]);
    $emitter = new CapturingEmitter();

    $app = new App(makeRequest('GET', '/test/api/demo', '', ['include' => 'details']), $router, $emitter);
    $app->start();

    $payload = json_decode($emitter->content, true);

    assertSameValue('demo', $payload['data']['arguments'][0] ?? null, 'PathParam should bind route value.');
    assertSameValue('details', $payload['data']['include'] ?? null, 'QueryParam should bind query value.');
};

$tests['app applies status header and body attributes'] = function (): void {
    $router = new Router();
    (new AttributeRouteLoader())->load($router, [TestAttributeController::class]);
    $emitter = new CapturingEmitter();

    $app = new App(makeRequest('POST', '/test/created', '{"name":"Shift"}'), $router, $emitter);
    $app->start();

    $payload = json_decode($emitter->content, true);

    assertSameValue(201, $emitter->statusCode, 'Status attribute should override response status.');
    assertArrayHasKeyValue('X-Test', 'created', $emitter->headers, 'Header attribute should add response header.');
    assertSameValue('Shift', $payload['name'] ?? null, 'Body attribute should bind JSON body key.');
};

$tests['module loader registers services and routes'] = function (): void {
    $loader = (new ModuleLoader())->load();
    $router = new Router();
    $container = new ServiceContainer();

    $loader->registerServices($container);
    $loader->registerRoutes($router);

    assertSameValue(true, $container->has(HealthService::class), 'Health module service should be registered.');

    $routes = array_map(
        static fn (\Engine\Route $route): string => $route->getMethod() . ' ' . $route->getPath(),
        $router->getRoutes()
    );

    assertSameValue(['GET /health'], $routes, 'Health module route should be registered.');
};

$tests['app dispatches module controller with service'] = function (): void {
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
};

$failed = 0;

foreach ($tests as $name => $test) {
    try {
        $test();
        echo "[PASS] {$name}" . PHP_EOL;
    } catch (Throwable $exception) {
        $failed++;
        echo "[FAIL] {$name}" . PHP_EOL;
        echo $exception->getMessage() . PHP_EOL;
    }
}

if ($failed > 0) {
    exit(1);
}

echo count($tests) . ' tests passed.' . PHP_EOL;
