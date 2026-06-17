<?php

namespace Console\Commands;

use Shift\Console\CommandInterface;
use Shift\Console\Generator\GeneratesFiles;
use Shift\Console\Generator\NameFormatter;

class CreateDto implements CommandInterface
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

        $class = NameFormatter::className($rawName, 'Dto');
        $path = $this->modulePath($module) . '/Dto/' . $class . '.php';

        $this->writeAndReport($path, $this->renderStub('dto', [
            'module' => $module,
            'class' => $class,
        ]));
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift create:dto --module={name} {DtoName}';
    }

    public function getDescription(): string
    {
        return 'Create a module request DTO.';
    }

}
