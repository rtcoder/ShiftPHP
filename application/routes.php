<?php

use Controllers\HelloController;
use Engine\Router;
use Engine\Routing\AttributeRouteLoader;

return static function (Router $router): void {
    (new AttributeRouteLoader())->load($router, [
        HelloController::class,
    ]);
};
