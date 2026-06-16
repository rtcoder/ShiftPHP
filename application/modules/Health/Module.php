<?php

namespace Modules\Health;

use Engine\Modules\AbstractModule;
use Engine\Router;
use Engine\Routing\AttributeRouteLoader;
use Engine\Service\ServiceContainer;
use Modules\Health\Controllers\HealthController;
use Modules\Health\Services\HealthService;

class Module extends AbstractModule
{
    public function getName(): string
    {
        return 'health';
    }

    public function registerServices(ServiceContainer $container): void
    {
        $container->singleton(HealthService::class, HealthService::class);
    }

    public function registerRoutes(Router $router): void
    {
        (new AttributeRouteLoader())->load($router, [
            HealthController::class,
        ]);
    }

    public function getCommandMappings(): array
    {
        return [
            [
                'dir' => __DIR__ . '/Commands/',
                'namespace' => 'Modules\\Health\\Commands\\',
            ],
        ];
    }
}
