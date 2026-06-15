# ShiftPHP

ShiftPHP is a small API-only PHP framework.

Version `0.5` focuses on the HTTP API core:

- explicit routes,
- controller actions,
- JSON responses,
- request helpers,
- JSON error responses,
- a small service container.

## Requirements

- PHP 8.3 or higher
- Composer

## Routing

Routes are registered in `application/routes.php`:

```php
use Controllers\HelloController;
use Engine\Router;
use Engine\Routing\AttributeRouteLoader;

return static function (Router $router): void {
    (new AttributeRouteLoader())->load($router, [
        HelloController::class,
    ]);
};
```

Supported methods:

- `get`
- `post`
- `put`
- `patch`
- `delete`

## Controllers

Controllers extend `Engine\Controller` and return a response object:

```php
namespace Controllers;

use Engine\Controller;
use Engine\JsonResponse;
use Engine\Request;
use Engine\Routing\Attributes\Get;
use Engine\Routing\Attributes\Post;
use Engine\Routing\Attributes\RoutePrefix;

#[RoutePrefix('/users')]
class UserController extends Controller
{
    #[Get('/{id}')]
    public function show(Request $request, string $id): JsonResponse
    {
        return $this->json([
            'id' => $id,
            'include' => $request->query('include'),
        ]);
    }

    #[Post('')]
    public function create(Request $request): JsonResponse
    {
        return $this->json($request->getJson(), 201);
    }
}
```

Route parameters are passed by method parameter name. A controller action can also request the current `Engine\Request`.

You can use these routing attributes:

- `#[RoutePrefix('/prefix')]`
- `#[Get('/path')]`
- `#[Post('/path')]`
- `#[Put('/path')]`
- `#[Patch('/path')]`
- `#[Delete('/path')]`

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

## Run Locally

```sh
php -S 127.0.0.1:8000 index.php
```

Then open:

```text
http://127.0.0.1:8000/hello
http://127.0.0.1:8000/hello/api/example
```

## CLI

List registered API routes:

```sh
php shift.php route:list
```

## Tests

Run the lightweight API core test suite:

```sh
composer test
```

## Release Process

Every pull request must have exactly one version label, for example `v0.6`.

After a versioned PR is merged into `master`, GitHub Actions creates the matching git tag and GitHub Release automatically.
