<?php

namespace Shift\Modules;

use Shift\Routing\Router\Router;
use Shift\Service\ServiceContainer;

abstract class AbstractModule implements ModuleInterface
{
    public function registerServices(ServiceContainer $container): void
    {
    }

    public function registerRoutes(Router $router): void
    {
    }

    public function getCommandMappings(): array
    {
        return [];
    }
}
