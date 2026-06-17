<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Modules\ModuleLoader;

class CacheModules implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $loader = new ModuleLoader();
        $count = $loader->cache();
        $cacheFile = $loader->getCacheFile();

        $cli->success('Cached modules: ' . $count);

        if ($cacheFile !== null) {
            $cli->debug('Cache file: ' . $cacheFile);
        }
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift cache:modules';
    }

    public function getDescription(): string
    {
        return 'Cache discovered modules for production.';
    }
}
