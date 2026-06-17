<?php

namespace Shift\Modules;

use Shift\Routing\Router\Router;
use Shift\Service\ServiceContainer;

class ModuleLoader
{
    /** @var ModuleInterface[] */
    private array $modules = [];
    private array $config = [];

    public function __construct(
        private readonly string $modulesPath = APP_PATH . '/modules',
        private readonly ?string $cacheFile = APP_ROOT . '/storage/cache/modules.php'
    ) {
    }

    public function load(): self
    {
        $this->modules = [];
        $this->config = [];

        if ($this->cacheFile !== null && is_file($this->cacheFile)) {
            return $this->loadFromCache();
        }

        return $this->loadFromSnapshot($this->discover());
    }

    public function cache(): int
    {
        $snapshot = $this->discover();
        $this->writeCache($snapshot);
        $this->modules = [];
        $this->config = [];
        $this->loadFromSnapshot($snapshot);

        return count($snapshot['modules']);
    }

    public function clearCache(): bool
    {
        if ($this->cacheFile === null || !is_file($this->cacheFile)) {
            return false;
        }

        return unlink($this->cacheFile);
    }

    public function isCached(): bool
    {
        return $this->cacheFile !== null && is_file($this->cacheFile);
    }

    public function getCacheFile(): ?string
    {
        return $this->cacheFile;
    }

    /**
     * @return array{generated_at: string, modules_path: string, modules: list<array{file: string, class: string, name: string, config: array}>}
     */
    private function discover(): array
    {
        $modules = [];

        if (is_dir($this->modulesPath)) {
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
                    $modules[] = [
                        'file' => $moduleFile,
                        'class' => $moduleClass,
                        'name' => $module->getName(),
                        'config' => array_replace_recursive(
                            $this->loadConfigFile($modulePath),
                            $module->getConfig()
                        ),
                    ];
                }
            }
        }

        return [
            'generated_at' => date(DATE_ATOM),
            'modules_path' => $this->modulesPath,
            'modules' => $modules,
        ];
    }

    private function loadFromCache(): self
    {
        $snapshot = require $this->cacheFile;

        if (!is_array($snapshot)) {
            return $this->loadFromSnapshot($this->discover());
        }

        return $this->loadFromSnapshot($snapshot);
    }

    /**
     * @param array{modules?: list<array{file?: string, class?: string, name?: string, config?: array}>} $snapshot
     */
    private function loadFromSnapshot(array $snapshot): self
    {
        foreach ($snapshot['modules'] ?? [] as $entry) {
            $file = $entry['file'] ?? null;
            $class = $entry['class'] ?? null;

            if (!is_string($file) || !is_string($class) || !is_file($file)) {
                continue;
            }

            require_once $file;

            if (!class_exists($class)) {
                continue;
            }

            $module = new $class();

            if ($module instanceof ModuleInterface) {
                $name = is_string($entry['name'] ?? null) ? $entry['name'] : $module->getName();
                $this->modules[] = $module;
                $this->config[$name] = is_array($entry['config'] ?? null) ? $entry['config'] : [];
            }
        }

        return $this;
    }

    /**
     * @param array{generated_at: string, modules_path: string, modules: list<array{file: string, class: string, name: string, config: array}>} $snapshot
     */
    private function writeCache(array $snapshot): void
    {
        if ($this->cacheFile === null) {
            return;
        }

        $directory = dirname($this->cacheFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents(
            $this->cacheFile,
            "<?php\n\nreturn " . var_export($snapshot, true) . ";\n"
        );
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
