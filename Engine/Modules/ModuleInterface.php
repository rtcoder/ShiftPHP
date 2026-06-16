<?php

namespace Shift\Modules;

use Shift\Routing\Router\Router;
use Shift\Service\ServiceContainer;

interface ModuleInterface
{
    public function getName(): string;

    public function registerServices(ServiceContainer $container): void;

    public function registerRoutes(Router $router): void;

    public function getCommandMappings(): array;
}
