<?php

namespace Engine\Modules;

use Engine\Routing\Router\Router;
use Engine\Service\ServiceContainer;

class ModuleLoader
{
    /** @var ModuleInterface[] */
    private array $modules = [];

    public function __construct(private readonly string $modulesPath = APP_PATH . '/modules')
    {
    }

    public function load(): self
    {
        if (!is_dir($this->modulesPath)) {
            return $this;
        }

        foreach (glob($this->modulesPath . '/*/Module.php') ?: [] as $moduleFile) {
            require_once $moduleFile;

            $moduleName = basename(dirname($moduleFile));
            $moduleClass = 'Modules\\' . $moduleName . '\\Module';

            if (!class_exists($moduleClass)) {
                continue;
            }

            $module = new $moduleClass();

            if ($module instanceof ModuleInterface) {
                $this->modules[] = $module;
            }
        }

        return $this;
    }

    public function registerServices(ServiceContainer $container): void
    {
        foreach ($this->modules as $module) {
            $module->registerServices($container);
        }
    }

    public function registerRoutes(Router $router): void
    {
        foreach ($this->modules as $module) {
            $module->registerRoutes($router);
        }
    }

    public function getCommandMappings(): array
    {
        $mappings = [];

        foreach ($this->modules as $module) {
            foreach ($module->getCommandMappings() as $mapping) {
                $mappings[] = $mapping;
            }
        }

        return $mappings;
    }

    public function getModules(): array
    {
        return $this->modules;
    }
}
