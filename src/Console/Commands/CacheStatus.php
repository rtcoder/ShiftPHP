<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Modules\ModuleLoader;

#[\Shift\Console\Attributes\Command('cache:status', aliases: ['cs'], group: 'cache')]
class CacheStatus implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $loader = new ModuleLoader();
        $cacheFile = $loader->getCacheFile();
        $exists = $loader->isCached();

        (new Cli())->table(['Cache', 'Status', 'Path'], [
            ['modules', $exists ? 'cached' : 'empty', $cacheFile ?? 'disabled'],
        ]);
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift cache:status';
    }

    public function getDescription(): string
    {
        return 'Show framework cache status.';
    }
}
