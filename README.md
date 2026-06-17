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
- environment configuration,
- PDO database queries,
- middleware pipeline,
- validation helpers and typed request DTOs,
- JSON error responses,
- a small service container.

## Requirements

- PHP 8.3 or higher
- Composer
- `json` and `pdo` PHP extensions

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

## Environment

ShiftPHP loads `.env` from the project root during bootstrap. Use `.env.example` as the starting point:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shift
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

Existing server environment variables are not overwritten by `.env`.

## Database

Database access uses native PDO and is registered lazily in the service container as `Shift\Database\Database` and `db`:

```php
use Shift\Database\Database;

class UserService
{
    public function __construct(private readonly Database $db)
    {
    }

    public function find(int $id): ?array
    {
        return $this->db
            ->query('select * from users where id = :id', ['id' => $id])
            ->first();
    }
}
```

Available helpers are `query($sql, $parameters)`, `execute($sql, $parameters)`, `pdo()`, and `transaction($callback)`. Query results expose `all()`, `first()`, `value()`, `affectedRows()`, and the raw `PDOStatement`.

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

Generate module scaffolding:

```sh
php shift.php create:module Billing
```

Generate module-owned classes:

```sh
php shift.php create:controller --module=Billing InvoiceController
php shift.php create:controller Billing:InvoiceController
php shift.php create:model Billing:Invoice
php shift.php create:service Billing:Invoice
php shift.php create:command Billing:SyncInvoices
php shift.php create:middleware Billing:Audit
php shift.php create:dto Billing:CreateInvoice
```

Generator commands normalize module and class names to PHP class conventions. Missing suffixes are added for controllers, services, middleware, and DTOs.
Generator templates live in `src/Console/Generator/stubs`.

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

Generated models, middleware, and DTOs live under `Models/`, `Middleware/`, and `Dto/` inside the target module.

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
