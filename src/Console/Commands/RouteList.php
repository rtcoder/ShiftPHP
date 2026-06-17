<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Modules\ModuleLoader;
use Shift\Routing\Router\Router;

#[\Shift\Console\Attributes\Command('route:list', aliases: ['routes', 'rl'], group: 'routing')]
class RouteList implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $router = new Router();
        (new ModuleLoader())->load()->registerRoutes($router);

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
        return 'Usage: ./shift route:list';
    }

    public function getDescription(): string
    {
        return 'List registered API routes.';
    }
}
