<?php

use Modules\Health\Services\HealthService;
use Shift\App;
use Shift\Modules\ModuleLoader;
use Shift\Routing\Router\Route;
use Shift\Routing\Router\Router;
use Shift\Service\ServiceContainer;

return [
    'module loader registers services and routes' => function (): void {
        $loader = (new ModuleLoader())->load();
        $router = new Router();
        $container = new ServiceContainer();

        $loader->registerServices($container);
        $loader->registerRoutes($router);
        $loader->boot($container);

        assertSameValue(true, $container->has(HealthService::class), 'Health module service should be registered.');
        assertSameValue(true, $container->resolve('health.booted'), 'Health module boot hook should run.');
        assertSameValue(true, $loader->getConfig('health')['enabled'] ?? null, 'Health module config file should load.');
        assertSameValue('health', $loader->getConfig('health')['module'] ?? null, 'Health module config method should merge.');

        $routes = array_map(
            static fn (Route $route): string => $route->getMethod() . ' ' . $route->getPath(),
            $router->getRoutes()
        );

        assertSameValue(['GET /health'], $routes, 'Health module route should be registered.');
    },

    'app dispatches module controller with service' => function (): void {
        $loader = (new ModuleLoader())->load();
        $router = new Router();
        $emitter = new CapturingEmitter();
        $app = new App(makeRequest('GET', '/health'), $router, $emitter);

        $loader->registerServices($app->getContainer());
        $loader->registerRoutes($router);
        $app->start();

        $payload = json_decode($emitter->content, true);

        assertSameValue(200, $emitter->statusCode, 'Module route should emit successful status.');
        assertSameValue('health', $payload['module'] ?? null, 'Module controller should resolve its service.');
    },
    'module loader can cache discovered module metadata' => function (): void {
        $root = sys_get_temp_dir() . '/shift-module-cache-' . bin2hex(random_bytes(6));
        $modulesPath = $root . '/modules';
        $cacheFile = $root . '/cache/modules.php';
        $moduleName = 'Cached' . bin2hex(random_bytes(4));
        $moduleSlug = strtolower($moduleName);

        try {
            writeCachedTestModule($modulesPath, $moduleName, $moduleSlug, true);

            $loader = new ModuleLoader($modulesPath, $cacheFile);
            $cached = $loader->cache();

            assertSameValue(1, $cached, 'Module cache should include discovered modules.');
            assertFileExists($cacheFile, 'Module cache file should be written.');

            writeCachedTestModule($modulesPath, $moduleName, $moduleSlug, false);

            $cachedLoader = (new ModuleLoader($modulesPath, $cacheFile))->load();
            assertSameValue(true, $cachedLoader->isCached(), 'Loader should detect an existing module cache.');
            assertSameValue(true, $cachedLoader->getConfig($moduleSlug)['enabled'] ?? null, 'Cached config should be loaded from snapshot.');
            assertSameValue(true, $cachedLoader->clearCache(), 'Module cache should be removable.');
            assertSameValue(false, is_file($cacheFile), 'Cache file should be removed after clear.');
        } finally {
            removeDirectory($root);
        }
    },
];

function writeCachedTestModule(string $modulesPath, string $moduleName, string $moduleSlug, bool $enabled): void
{
    $modulePath = $modulesPath . '/' . $moduleName;

    if (!is_dir($modulePath)) {
        mkdir($modulePath, 0775, true);
    }

    file_put_contents($modulePath . '/config.php', "<?php\n\nreturn ['enabled' => " . ($enabled ? 'true' : 'false') . "];\n");
    file_put_contents($modulePath . '/Module.php', <<<PHP
<?php

namespace Modules\\{$moduleName};

use Shift\\Modules\\AbstractModule;

class Module extends AbstractModule
{
    public function getName(): string
    {
        return '{$moduleSlug}';
    }
}
PHP);
}
