# ShiftPHP

ShiftPHP is a small API-only PHP framework.

The current architecture focuses on an API-only modular monolith:

- module-owned routes,
- module-owned controllers,
- module-owned services,
- module-owned CLI commands,
- controller actions,
- JSON responses,
- request helpers,
- middleware pipeline,
- validation helpers and typed request DTOs,
- JSON error responses,
- a small service container.

## Requirements

- PHP 8.3 or higher
- Composer

## Routing

Routes are owned by modules and registered from each module boundary:

```php
namespace Modules\Health;

use Shift\Modules\AbstractModule;use Shift\Routing\AttributeRouteLoader;use Shift\Routing\Router\Router;use Modules\Health\Controllers\HealthController;

class Module extends AbstractModule
{
    public function registerRoutes(Router $router): void
    {
        (new AttributeRouteLoader())->load($router, [
            HealthController::class,
        ]);
    }
}
```

Supported methods:

- `get`
- `post`
- `put`
- `patch`
- `delete`

## Controllers

Controllers extend `Shift\Controller` and return a response object:

```php
namespace Modules\Users\Controllers;

use Shift\Controller;
use Shift\Response\JsonResponse;
use Shift\Routing\Attributes\Body;
use Shift\Routing\Attributes\Get;
use Shift\Routing\Attributes\Header;
use Shift\Routing\Attributes\PathParam;
use Shift\Routing\Attributes\Post;
use Shift\Routing\Attributes\QueryParam;
use Shift\Routing\Attributes\RoutePrefix;
use Shift\Routing\Attributes\Status;

#[RoutePrefix('/users')]
class UserController extends Controller
{
    #[Get('/{id}')]
    public function show(#[PathParam] int $id, #[QueryParam('include')] ?string $include = null): JsonResponse
    {
        return $this->json([
            'id' => $id,
            'include' => $include,
        ]);
    }

    #[Post('')]
    #[Status(201)]
    #[Header('X-Resource', 'created')]
    public function create(#[Body] array $payload): array
    {
        return $payload;
    }
}
```

Route parameters are passed by method parameter name. A controller action can also request the current `Shift\Request`.

Controllers are created through the service container, so constructor dependencies can be type-hinted:

```php
class UserController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    #[Get('/{id}')]
    public function show(#[PathParam] int $id): array
    {
        return $this->users->find($id);
    }
}
```

You can use these routing attributes:

- `#[RoutePrefix('/prefix')]`
- `#[Get('/path')]`
- `#[Post('/path')]`
- `#[Put('/path')]`
- `#[Patch('/path')]`
- `#[Delete('/path')]`
- `#[Status(201)]`
- `#[Header('X-Name', 'value')]`
- `#[PathParam('id')]`
- `#[QueryParam('include')]`
- `#[Body]` or `#[Body('field')]`
- `#[BodyDto]`

## Responses

Use the controller helpers:

```php
return $this->json(['status' => 'ok']);
return $this->json($payload, 201);
return $this->error('Invalid payload', 422);
return $this->noContent();
```

## Request Helpers

```php
$request->getMethod();
$request->getPath();
$request->query('page', 1);
$request->post('name');
$request->input('name');
$request->getJson();
$request->getHeader('Authorization');
$request->routeParam('id');
```

Malformed JSON bodies are returned as `400 Bad Request`.

## Validation and DTOs

Request DTOs extend `Shift\Validation\RequestDto` and define validation rules:

```php
use Shift\Validation\RequestDto;

final class CreateUserDto extends RequestDto
{
    public function __construct(
        public readonly string $email,
        public readonly int $age
    ) {
    }

    public static function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'age' => 'required|int|min:18',
        ];
    }
}
```

DTOs can be bound by type or with `#[BodyDto]`:

```php
#[Post('/users')]
public function create(#[BodyDto] CreateUserDto $dto): array
{
    return ['email' => $dto->email, 'age' => $dto->age];
}
```

Validation failures are returned as `422` JSON responses. Supported rules are `required`, `string`, `int`, `bool`, `array`, `email`, `min`, and `max`.

## Middleware

Middleware can wrap or stop request handling before the controller action runs:

```php
use Shift\Middleware\MiddlewareInterface;
use Shift\Request;
use Shift\Response\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if ($request->getHeader('Authorization') === null) {
            return new Response('Unauthorized', 401);
        }

        return $next($request);
    }
}

$app->middleware(AuthMiddleware::class);
```

Callable middleware is also supported:

```php
$app->middleware(function (Request $request, callable $next): Response {
    $response = $next($request);

    return new Response(
        $response->getContent(),
        $response->getStatusCode(),
        $response->getHeaders() + ['X-Api' => 'Shift']
    );
});
```

Built-in middleware includes `Shift\Middleware\CorsMiddleware`, `Shift\Middleware\AuthMiddleware`, and `Shift\Middleware\AuthorizationMiddleware`. Auth middleware uses `Shift\Auth\AuthenticatorInterface`; authorization middleware uses `Shift\Auth\AuthorizerInterface`.

## Service Container

The container can resolve registered services and build classes with typed constructor dependencies:

```php
$container->singleton(UserService::class, UserService::class);
$service = $container->resolve(UserService::class);
$controller = $container->make(UserController::class);
```

## Run Locally

```sh
php -S 127.0.0.1:8000 index.php
```

Then open:

```text
http://127.0.0.1:8000/health
```

## CLI

List registered API routes:

```sh
php shift.php route:list
```

Run the example module command:

```sh
php shift.php health
```

## Modules

ShiftPHP supports a modular monolith structure under `application/modules`.

Each module can own its controllers, routes, services, and CLI commands:

```text
application/modules/Health/
├── Module.php
├── Controllers/
├── Services/
└── Commands/
```

A module registers itself through `Module.php`:

```php
namespace Modules\Health;

use Shift\Modules\AbstractModule;use Shift\Routing\AttributeRouteLoader;use Shift\Routing\Router\Router;use Shift\Service\ServiceContainer;use Modules\Health\Controllers\HealthController;use Modules\Health\Services\HealthService;

class Module extends AbstractModule
{
    public function getName(): string
    {
        return 'health';
    }

    public function registerServices(ServiceContainer $container): void
    {
        $container->singleton(HealthService::class, HealthService::class);
    }

    public function registerRoutes(Router $router): void
    {
        (new AttributeRouteLoader())->load($router, [
            HealthController::class,
        ]);
    }

    public function getCommandMappings(): array
    {
        return [
            [
                'dir' => __DIR__ . '/Commands/',
                'namespace' => 'Modules\\Health\\Commands\\',
            ],
        ];
    }
}
```

Modules are loaded automatically by convention from `application/modules/*/Module.php`.

Modules may expose config through `config.php` and `getConfig()`. Config is available from the loader with `$modules->getConfig()` and in the container under `modules.config`. Modules may also implement `boot(ServiceContainer $container)` for work that should run after service registration.

## Tests

Run the lightweight API core test suite:

```sh
composer test
```

The test runner lives in `tests/ApiCoreTest.php`. Shared helpers and fixtures live in `tests/Support` and `tests/Fixtures`, while feature test files live in `tests/Feature`.

## Release Process

Every pull request must have exactly one version label, for example `v0.6`.

After a versioned PR is merged into `master`, GitHub Actions creates the matching git tag and GitHub Release automatically.
