# ShiftPHP 0.5 - API-only refactoring

ShiftPHP 0.5 turns the framework into an API-only HTTP core. View templates, compiled view storage, page assets and MVC rendering are out of scope for this line.

## 0.5 Scope

Implemented in this branch:

- explicit `application/routes.php` routing,
- route parameters with `{name}` placeholders,
- controller actions returning response objects,
- `Response`, `JsonResponse` and `ResponseEmitter`,
- JSON response helpers on the base controller,
- request helpers for query, post, input, raw body, JSON and route params,
- JSON errors for `400`, `404` and `500`,
- `405 Method Not Allowed` with an `Allow` header,
- lightweight API core tests through `composer test`,
- `route:list` CLI command,
- removal of view storage and example page assets from runtime.

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

Routes live in `application/routes.php`:

```php
return static function (Router $router): void {
    $router->get('/hello', [HelloController::class, 'index']);
    $router->get('/hello/api/{argument}', [HelloController::class, 'api']);
};
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
class HelloController extends \Engine\Controller
{
    public function api(string $argument): JsonResponse
    {
        return $this->json([
            'argument' => $argument,
        ]);
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
