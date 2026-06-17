<?php

namespace Console\Commands;

use Shift\Console\CommandInterface;
use Shift\Console\Generator\GeneratesFiles;
use Shift\Console\Generator\NameFormatter;

class CreateService implements CommandInterface
{
    use GeneratesFiles;

    public function execute(mixed ...$args): void
    {
        try {
            [$module, $rawName] = $this->moduleAndClassFromArgs($args);
        } catch (\InvalidArgumentException) {
            $this->cli->error($this->getHelp());
            return;
        }

        $class = NameFormatter::className($rawName, 'Service');
        $path = $this->modulePath($module) . '/Services/' . $class . '.php';

        $this->writeAndReport($path, $this->renderStub('service', [
            'module' => $module,
            'class' => $class,
        ]));
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift create:service --module={name} {ServiceName}';
    }

    public function getDescription(): string
    {
        return 'Create a module service.';
    }

}
