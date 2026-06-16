# ShiftPHP - API-only modular monolith

ShiftPHP is moving toward an API-only modular monolith. View templates, compiled view storage, page assets and MVC rendering are out of scope for this line.

## 0.5 Scope

Implemented in this branch:

- module-owned routing,
- route parameters with `{name}` placeholders,
- controller actions returning response objects,
- `Response`, `JsonResponse` and `ResponseEmitter`,
- JSON response helpers on the base controller,
- request helpers for query, post, input, raw body, JSON and route params,
- JSON errors for `400`, `404` and `500`,
- `405 Method Not Allowed` with an `Allow` header,
- lightweight API core tests through `composer test`,
- `route:list` CLI command,
- PHP 8 attributes for controller routing,
- PHP 8 attributes for response metadata and parameter binding,
- modular monolith support through `application/modules/*/Module.php`,
- removal of view storage and example page assets from runtime.

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
class HealthController extends \Engine\Controller
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

Action arguments are resolved from route parameter names. An action can also type-hint `Engine\Request`.

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

- `Engine/View`
- `Engine\View`
- `Engine/Utils/Storage.php`
- `Engine/Error/StorageError.php`
- example CSS and JS page assets
- `View\\` composer namespace

## Next After 0.5

- Middleware pipeline.
- Controller autowiring through the container.
- Validation helpers and typed request DTOs.
- CORS and auth middleware.
- Structured logging for exceptions.
