<?php

namespace Console\Commands;

use Engine\Console\Cli;
use Engine\Console\CommandInterface;
use Engine\Modules\ModuleLoader;
use Engine\Router;

class RouteList implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $router = new Router();
        (new ModuleLoader())->load()->registerRoutes($router);
        $routesFile = APP_PATH . '/routes.php';

        if (!file_exists($routesFile)) {
            $cli->warning('No routes file found.');
            return;
        }

        $registerRoutes = require $routesFile;
        $registerRoutes($router);

        $rows = [];
        foreach ($router->getRoutes() as $route) {
            [$controllerClass, $method] = $route->getHandler();
            $rows[] = [
                $route->getMethod(),
                $route->getPath(),
                $controllerClass . '@' . $method,
            ];
        }

        if ($rows === []) {
            $cli->warning('No routes registered.');
            return;
        }

        $cli->table(['Method', 'Path', 'Handler'], $rows);
    }

    public function getHelp(): string
    {
        return 'Usage: php shift.php route:list';
    }

    public function getDescription(): string
    {
        return 'List registered API routes.';
    }
}
