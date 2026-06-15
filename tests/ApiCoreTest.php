<?php

use Controllers\HelloController;
use Engine\App;
use Engine\Error\HttpError;
use Engine\JsonResponse;
use Engine\Request;
use Engine\ResponseEmitter;
use Engine\Router;
use Engine\Routing\AttributeRouteLoader;

require_once __DIR__ . '/../bootstrap.php';

final class CapturingEmitter extends ResponseEmitter
{
    public int $statusCode = 0;
    public array $headers = [];
    public string $content = '';

    public function emit(\Engine\Response $response): void
    {
        $this->statusCode = $response->getStatusCode();
        $this->headers = $response->getHeaders();
        $this->content = $response->getContent();
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
    $router->get('/hello/api/{argument}', [HelloController::class, 'api']);

    $match = $router->match(makeRequest('GET', '/hello/api/example'));

    assertSameValue(['argument' => 'example'], $match->getParameters(), 'Route parameters should be extracted.');
};

$tests['attribute loader registers controller routes'] = function (): void {
    $router = new Router();
    (new AttributeRouteLoader())->load($router, [HelloController::class]);

    $routes = array_map(
        static fn (\Engine\Route $route): string => $route->getMethod() . ' ' . $route->getPath(),
        $router->getRoutes()
    );

    assertSameValue(
        [
            'GET /hello',
            'GET /hello/about',
            'GET /hello/api',
            'GET /hello/api/{argument}',
            'POST /hello/echo',
            'POST /hello/created',
        ],
        $routes,
        'Attribute loader should register all HelloController routes.'
    );
};

$tests['router returns 405 with Allow header'] = function (): void {
    $router = new Router();
    $router->get('/hello', [HelloController::class, 'index']);

    try {
        $router->match(makeRequest('POST', '/hello'));
    } catch (HttpError $error) {
        assertSameValue(405, $error->getStatusCode(), 'Wrong method should return 405.');
        assertArrayHasKeyValue('Allow', 'GET', $error->getHeaders(), '405 should expose allowed methods.');
        return;
    }

    throw new RuntimeException('Expected HttpError was not thrown.');
};

$tests['request parses json and headers'] = function (): void {
    $request = makeRequest('POST', '/hello/echo', '{"name":"Shift"}');

    assertSameValue(['name' => 'Shift'], $request->getJson(), 'JSON body should parse.');
    assertSameValue('Shift', $request->input('name'), 'Input should read JSON body.');
    assertSameValue('Bearer token', $request->getHeader('Authorization'), 'Header should be available.');
};

$tests['request rejects malformed json'] = function (): void {
    $request = makeRequest('POST', '/hello/echo', '{bad');

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
    $router->get('/hello/api/{argument}', [HelloController::class, 'api']);
    $emitter = new CapturingEmitter();

    $app = new App(makeRequest('GET', '/hello/api/demo'), $router, $emitter);
    $app->start();

    $payload = json_decode($emitter->content, true);

    assertSameValue(200, $emitter->statusCode, 'App should emit successful status.');
    assertSameValue('demo', $payload['data']['routeParams']['argument'] ?? null, 'App should pass route params to controller.');
};

$tests['app binds path and query params from attributes'] = function (): void {
    $router = new Router();
    (new AttributeRouteLoader())->load($router, [HelloController::class]);
    $emitter = new CapturingEmitter();

    $app = new App(makeRequest('GET', '/hello/api/demo', '', ['include' => 'details']), $router, $emitter);
    $app->start();

    $payload = json_decode($emitter->content, true);

    assertSameValue('demo', $payload['data']['arguments'][0] ?? null, 'PathParam should bind route value.');
    assertSameValue('details', $payload['data']['include'] ?? null, 'QueryParam should bind query value.');
};

$tests['app applies status header and body attributes'] = function (): void {
    $router = new Router();
    (new AttributeRouteLoader())->load($router, [HelloController::class]);
    $emitter = new CapturingEmitter();

    $app = new App(makeRequest('POST', '/hello/created', '{"name":"Shift"}'), $router, $emitter);
    $app->start();

    $payload = json_decode($emitter->content, true);

    assertSameValue(201, $emitter->statusCode, 'Status attribute should override response status.');
    assertArrayHasKeyValue('X-ShiftPHP-Example', 'created', $emitter->headers, 'Header attribute should add response header.');
    assertSameValue('Shift', $payload['name'] ?? null, 'Body attribute should bind JSON body key.');
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
