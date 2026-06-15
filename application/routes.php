<?php

use Controllers\HelloController;
use Engine\Router;

return static function (Router $router): void {
    $router->get('/hello', [HelloController::class, 'index']);
    $router->get('/hello/about', [HelloController::class, 'about']);
    $router->get('/hello/api', [HelloController::class, 'api']);
    $router->get('/hello/api/{argument}', [HelloController::class, 'api']);
    $router->post('/hello/echo', [HelloController::class, 'echo']);
};
