<?php

namespace Engine\Modules;

use Engine\Routing\Router\Router;
use Engine\Service\ServiceContainer;

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
