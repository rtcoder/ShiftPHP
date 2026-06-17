<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Modules\ModuleLoader;

#[\Shift\Console\Attributes\Command('cache:clear', aliases: ['cc'], group: 'cache')]
class CacheClear implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $cleared = (new ModuleLoader())->clearCache();

        if ($cleared) {
            $cli->success('Module cache cleared.');
            return;
        }

        $cli->info('Module cache is already empty.');
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift cache:clear';
    }

    public function getDescription(): string
    {
        return 'Clear framework cache files.';
    }
}
