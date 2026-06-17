<?php

namespace Shift\Modules;

use Shift\Routing\Router\Router;
use Shift\Service\ServiceContainer;

class ModuleLoader
{
    /** @var ModuleInterface[] */
    private array $modules = [];
    private array $config = [];

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

            $modulePath = dirname($moduleFile);
            $moduleName = basename($modulePath);
            $moduleClass = 'Modules\\' . $moduleName . '\\Module';

            if (!class_exists($moduleClass)) {
                continue;
            }

            $module = new $moduleClass();

            if ($module instanceof ModuleInterface) {
                $this->modules[] = $module;
                $this->config[$module->getName()] = array_replace_recursive(
                    $this->loadConfigFile($modulePath),
                    $module->getConfig()
                );
            }
        }

        return $this;
    }

    private function loadConfigFile(string $modulePath): array
    {
        $configFile = $modulePath . '/config.php';

        if (!is_file($configFile)) {
            return [];
        }

        $config = require $configFile;

        return is_array($config) ? $config : [];
    }

    public function registerServices(ServiceContainer $container): void
    {
        $container->singleton('modules.config', $this->config);

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

    public function boot(ServiceContainer $container): void
    {
        foreach ($this->modules as $module) {
            $module->boot($container);
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

    public function getConfig(?string $module = null): array
    {
        if ($module !== null) {
            return $this->config[$module] ?? [];
        }

        return $this->config;
    }
}
