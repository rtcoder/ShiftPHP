<?php

namespace Engine\Modules;

use Engine\Router;
use Engine\ServiceContainer;

interface ModuleInterface
{
    public function getName(): string;

    public function registerServices(ServiceContainer $container): void;

    public function registerRoutes(Router $router): void;

    public function getCommandMappings(): array;
}
