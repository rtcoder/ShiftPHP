# ShiftPHP - API-only modular monolith

ShiftPHP is moving toward an API-only modular monolith. View templates, compiled view storage, page assets and MVC rendering are out of scope for this line.

## Completed

- [x] API-only runtime direction.
- [x] Route parameters with `{name}` placeholders.
- [x] Controller actions returning response objects.
- [x] `Shift\Response\Response`, `JsonResponse` and `ResponseEmitter`.
- [x] JSON response helpers on the base controller.
- [x] Request helpers for query, post, input, raw body, JSON and route params.
- [x] JSON errors for `400`, `404` and `500`.
- [x] `405 Method Not Allowed` with an `Allow` header.
- [x] Lightweight API core tests through `composer test`.
- [x] `route:list` CLI command.
- [x] PHP 8 attributes for controller routing.
- [x] PHP 8 attributes for response metadata and parameter binding.
- [x] Modular monolith support through `application/modules/*/Module.php`.
- [x] Module-owned controllers, routes, services and commands.
- [x] Middleware pipeline.
- [x] Controller autowiring through the container.
- [x] Validation helpers and typed request DTOs.
- [x] CORS middleware.
- [x] Authentication and authorization middleware contracts.
- [x] Module configuration loading.
- [x] Module lifecycle hooks, for example `boot()` after service registration.
- [x] Framework source moved to `src/` for package split preparation.
- [x] CLI create generators for modules and module-owned classes with file-based stubs.
- [x] `.env` loading for application configuration.
- [x] Native PDO database configuration, lazy connection, and basic query API.
- [x] Fluent query builder and attribute-driven database models.
- [x] CLI diagnostics for tests, runtime info, environment, database, and modules.
- [x] CLI help listing and command-specific usage.
- [x] Centralized CLI command registry shared by the dispatcher and help command.
- [x] Database migrations with create, migrate, status, and rollback commands.
- [x] Module discovery cache for production.
- [x] Removal of view storage and example page assets from runtime.
- [x] Removal of legacy `application/controllers` and `application/routes.php`.
- [x] Domain-oriented framework namespaces:
  - `Shift\Response`
  - `Shift\Routing\Router`
  - `Shift\Routing\Attributes`
  - `Shift\Service`
  - `Shift\Modules`
- [x] GitHub API workflow with PHP 8.3 checks.
- [x] PR version label validation.
- [x] Release workflow using PR summary as release notes.
- [x] Split API core tests into runner, support, fixtures, and feature files.

## Modular Monolith Direction

Each module can own:

- controllers,
- routes,
- services,
- commands.

Module layout:

```text
application/modules/{ModuleName}/
├── Module.php
├── Controllers/
├── Services/
└── Commands/
```

`Module.php` is the module boundary. It registers services into the container, routes into the router, and command mappings into the CLI.

## Runtime Flow

```text
Request
  -> App
  -> Middleware pipeline
  -> Router
  -> Controller action
  -> Response
  -> ResponseEmitter
```

## Routes

Routes live inside modules:

```php
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

- `GET`
- `POST`
- `PUT`
- `PATCH`
- `DELETE`

## Controllers

Controllers receive the current `Request` and `ServiceContainer` through the constructor. Actions should return `Response` or `JsonResponse`.

```php
class HealthController extends \Shift\Controller
{
    #[Get('/api/{argument}')]
    public function api(#[PathParam] string $argument, #[QueryParam('include')] ?string $include = null): JsonResponse
    {
        return $this->json([
            'argument' => $argument,
            'include' => $include,
        ]);
    }

    #[Post('/created')]
    #[Status(201)]
    public function created(#[Body('name')] string $name): array
    {
        return ['name' => $name];
    }
}
```

Action arguments are resolved from route parameter names. An action can also type-hint `Shift\Request`.

## Error Format

Errors are emitted as JSON:

```json
{
  "error": {
    "message": "Endpoint not found",
    "status": 404
  }
}
```

Internal errors return a generic `500` message unless `display_errors` is enabled.

## Removed From Runtime

- legacy `Engine/View`
- legacy view namespace
- legacy `Engine/Utils/Storage.php`
- legacy `Engine/Error/StorageError.php`
- example CSS and JS page assets
- `View\\` composer namespace
- `application/controllers`
- `application/routes.php`

## Next

- [ ] Structured logging for exceptions.
- [ ] CLI command aliases and richer command metadata.
- [ ] Basic package-quality checks, for example static analysis and coding style.
