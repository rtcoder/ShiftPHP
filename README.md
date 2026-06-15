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

return static function (Router $router): void {
    $router->get('/hello', [HelloController::class, 'index']);
    $router->get('/hello/api/{argument}', [HelloController::class, 'api']);
    $router->post('/hello/echo', [HelloController::class, 'echo']);
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

class UserController extends Controller
{
    public function show(Request $request, string $id): JsonResponse
    {
        return $this->json([
            'id' => $id,
            'include' => $request->query('include'),
        ]);
    }
}
```

Route parameters are passed by method parameter name. A controller action can also request the current `Engine\Request`.

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
