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
$request->getRequestId();
$request->routeParam('id');
```

If the request does not include `X-Request-Id`, ShiftPHP generates one. The same id is emitted on every response as `X-Request-Id` and included in structured exception logs. Malformed JSON bodies are returned as `400 Bad Request`.

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
APP_ENV=local
LOG_ENABLED=false
LOG_PATH=storage/logs/shift.log
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shift
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

Existing server environment variables are not overwritten by `.env`.

## Logging

Structured exception logging is available through `Shift\Logging\LoggerInterface`. It uses a no-op logger by default and writes JSON lines when logging is enabled:

```env
LOG_ENABLED=true
LOG_PATH=storage/logs/shift.log
```

Each log record contains `timestamp`, `level`, `message`, and `context`. Exception context includes the exception class, status code, file, line, and request data such as method, path, IP, user agent, and `X-Request-Id` when present.

You can replace the logger through the service container:

```php
use Shift\Logging\LoggerInterface;

$app->getContainer()->singleton(LoggerInterface::class, new CustomLogger());
```

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

### Migrations

Create migration files under `database/migrations`:

```sh
./shift create:migration create_users_table
```

Migration files return an anonymous `Shift\Database\Migration` instance:

```php
use Shift\Database\Database;
use Shift\Database\Migration;

return new class extends Migration
{
    public function up(Database $db): void
    {
        $db->execute('create table users (id integer primary key autoincrement, email text not null)');
    }

    public function down(Database $db): void
    {
        $db->execute('drop table users');
    }
};
```

Run pending migrations, inspect their status, or roll back the latest batch:

```sh
./shift migrate
./shift migrate:status
./shift migrate:rollback
```

### Query Builder and Models

Use the table query builder for simple fluent queries:

```php
$users = $db->table('users')
    ->select('id', 'email')
    ->where('active', true)
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();
```

Models extend `Shift\Database\Model`. Public properties represent database columns:

```php
use Shift\Database\Attributes\Cast;
use Shift\Database\Attributes\Guarded;
use Shift\Database\Attributes\PrimaryKey;
use Shift\Database\Model;

class User extends Model
{
    protected string $table = 'users';

    #[PrimaryKey]
    #[Cast('int')]
    public ?int $id = null;

    public string $email = '';

    #[Guarded]
    public string $role = 'user';

    #[Cast('array')]
    public array $meta = [];

    #[Cast('datetime')]
    public ?DateTimeImmutable $created_at = null;
}
```

Model queries return hydrated model instances:

```php
$user = User::query($db)->where('email', 'dev@example.com')->first();
$user = User::find(1, $db);
$user = User::create(['email' => 'dev@example.com'], $db);
$user->role = 'admin';
$user->save($db);
```

`#[Guarded]` fields are ignored during mass assignment through `create()` and query `update()`, but can be set explicitly on a model instance before `save()`. Supported casts include `int`, `float`, `bool`, `string`, `array`, `date`, `datetime`, and class names. Class casts use `fromArray()` when available.

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

The CLI discovers framework, application, and module commands through the command registry. Command names are declared with `#[Command]` attributes and can include aliases and groups. Command names are normalized, so `migrate:status`, `migrate-status`, and `migrate_status` resolve to the same command.

```php
use Shift\Console\Attributes\Command;
use Shift\Console\CommandInterface;

#[Command('billing:sync', aliases: ['sync-billing'], group: 'modules')]
final class SyncBilling implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift billing:sync';
    }

    public function getDescription(): string
    {
        return 'Sync billing data.';
    }
}
```

Show all commands or command-specific help:

```sh
./shift help
./shift help migrate
./shift help ms
```

List registered API routes:

```sh
./shift route:list
```

Run the test suite:

```sh
./shift test
```

Run local quality checks:

```sh
./shift lint
./shift qa
```

`shift lint` checks PHP syntax and basic file hygiene. `shift qa` runs Composer validation, lint checks, the test suite, and route listing.

Run the example module command:

```sh
./shift health
```

Inspect framework/runtime information:

```sh
./shift about
```

Check local environment and database configuration:

```sh
./shift doctor
./shift env:check
./shift db:check
```

List discovered modules:

```sh
./shift module:list
```

Cache discovered modules for production:

```sh
./shift cache:modules
./shift cache:status
./shift cache:clear
```

The module cache is stored in `storage/cache/modules.php`. Without that file, ShiftPHP discovers modules from `application/modules` on each run. After changing module boundaries, module config, or module command mappings in production, rebuild the cache.

Run database migrations:

```sh
./shift create:migration create_users_table
./shift migrate
./shift migrate:status
./shift migrate:rollback
```

Generate module scaffolding:

```sh
./shift create:module Billing
```

Generate module-owned classes:

```sh
./shift create:controller --module=Billing InvoiceController
./shift create:controller Billing:InvoiceController
./shift create:model Billing:Invoice
./shift create:service Billing:Invoice
./shift create:command Billing:SyncInvoices
./shift create:middleware Billing:Audit
./shift create:dto Billing:CreateInvoice
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
