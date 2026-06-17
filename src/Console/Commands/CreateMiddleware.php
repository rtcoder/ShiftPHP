<?php

namespace Console\Commands;

use Shift\Console\CommandInterface;
use Shift\Console\Generator\GeneratesFiles;
use Shift\Console\Generator\NameFormatter;

class CreateMiddleware implements CommandInterface
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

        $class = NameFormatter::className($rawName, 'Middleware');
        $path = $this->modulePath($module) . '/Middleware/' . $class . '.php';

        $this->writeAndReport($path, $this->renderStub('middleware', [
            'module' => $module,
            'class' => $class,
        ]));
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift create:middleware --module={name} {MiddlewareName}';
    }

    public function getDescription(): string
    {
        return 'Create a module middleware.';
    }

}
