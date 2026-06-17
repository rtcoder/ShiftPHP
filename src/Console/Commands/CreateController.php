<?php

namespace Console\Commands;

use Shift\Console\CommandInterface;
use Shift\Console\Generator\GeneratesFiles;
use Shift\Console\Generator\NameFormatter;

class CreateController implements CommandInterface
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

        $class = NameFormatter::className($rawName, 'Controller');
        $path = $this->modulePath($module) . '/Controllers/' . $class . '.php';

        $this->writeAndReport($path, $this->renderStub('controller', [
            'module' => $module,
            'class' => $class,
        ]));
    }

    public function getHelp(): string
    {
        return 'Usage: php shift.php create:controller --module={name} {ControllerName}';
    }

    public function getDescription(): string
    {
        return 'Create a module controller.';
    }

}
