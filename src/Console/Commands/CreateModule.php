<?php

namespace Console\Commands;

use Shift\Console\CommandInterface;
use Shift\Console\Generator\GeneratesFiles;
use Shift\Console\Generator\NameFormatter;

class CreateModule implements CommandInterface
{
    use GeneratesFiles;

    public function execute(mixed ...$args): void
    {
        $rawName = $args[0] ?? null;

        if (!is_string($rawName) || $rawName === '') {
            $this->cli->error($this->getHelp());
            return;
        }

        $module = NameFormatter::moduleName($rawName);
        $slug = NameFormatter::slug($rawName);
        $path = $this->modulePath($module);

        foreach (['Commands', 'Controllers', 'Services'] as $directory) {
            $this->files->ensureDirectory($path . '/' . $directory);
        }

        $this->writeAndReport($path . '/Module.php', $this->moduleStub($module, $slug));
        $this->writeAndReport($path . '/config.php', $this->configStub($slug));
    }

    public function getHelp(): string
    {
        return 'Usage: php shift.php create:module {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new Shift module.';
    }

    private function moduleStub(string $module, string $slug): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module};

use Shift\\Modules\\AbstractModule;
use Shift\\Routing\\AttributeRouteLoader;
use Shift\\Routing\\Router\\Router;
use Shift\\Service\\ServiceContainer;

class Module extends AbstractModule
{
    public function getName(): string
    {
        return '{$slug}';
    }

    public function registerServices(ServiceContainer \$container): void
    {
    }

    public function registerRoutes(Router \$router): void
    {
        (new AttributeRouteLoader())->load(\$router, [
        ]);
    }

    public function getCommandMappings(): array
    {
        return [
            [
                'dir' => __DIR__ . '/Commands/',
                'namespace' => 'Modules\\\\{$module}\\\\Commands\\\\',
            ],
        ];
    }
}

PHP;
    }

    private function configStub(string $slug): string
    {
        return <<<PHP
<?php

return [
    'enabled' => true,
    'module' => '{$slug}',
];

PHP;
    }
}
