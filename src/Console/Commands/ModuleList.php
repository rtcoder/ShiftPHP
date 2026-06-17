<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Modules\ModuleLoader;

#[\Shift\Console\Attributes\Command('module:list', aliases: ['modules'], group: 'modules')]
class ModuleList implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $loader = (new ModuleLoader())->load();
        $rows = [];

        foreach ($loader->getModules() as $module) {
            $config = $loader->getConfig($module->getName());
            $rows[] = [
                $module->getName(),
                $module::class,
                $config === [] ? 'no' : 'yes',
                count($module->getCommandMappings()),
            ];
        }

        if ($rows === []) {
            $cli->warning('No modules found.');
            return;
        }

        $cli->table(['Name', 'Class', 'Config', 'Commands'], $rows);
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift module:list';
    }

    public function getDescription(): string
    {
        return 'List discovered modules.';
    }
}
