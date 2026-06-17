<?php

namespace Console\Commands;

use Shift\Console\CommandInterface;
use Shift\Console\Generator\GeneratesFiles;
use Shift\Console\Generator\NameFormatter;

class CreateModel implements CommandInterface
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

        $class = NameFormatter::className($rawName);
        $path = $this->modulePath($module) . '/Models/' . $class . '.php';

        $this->writeAndReport($path, $this->stub($module, $class));
    }

    public function getHelp(): string
    {
        return 'Usage: php shift.php create:model --module={name} {ModelName}';
    }

    public function getDescription(): string
    {
        return 'Create a module model.';
    }

    private function stub(string $module, string $class): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Models;

class {$class}
{
}

PHP;
    }
}
