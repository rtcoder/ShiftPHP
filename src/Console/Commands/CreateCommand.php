<?php

namespace Console\Commands;

use Shift\Console\CommandInterface;
use Shift\Console\Generator\GeneratesFiles;
use Shift\Console\Generator\NameFormatter;

class CreateCommand implements CommandInterface
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
        $path = $this->modulePath($module) . '/Commands/' . $class . '.php';

        $this->writeAndReport($path, $this->stub($module, $class, NameFormatter::commandName($class)));
    }

    public function getHelp(): string
    {
        return 'Usage: php shift.php create:command --module={name} {CommandName}';
    }

    public function getDescription(): string
    {
        return 'Create a module CLI command.';
    }

    private function stub(string $module, string $class, string $command): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Commands;

use Shift\\Console\\Cli;
use Shift\\Console\\CommandInterface;

class {$class} implements CommandInterface
{
    public function execute(mixed ...\$args): void
    {
        (new Cli())->success('Command executed.');
    }

    public function getHelp(): string
    {
        return 'Usage: php shift.php {$command}';
    }

    public function getDescription(): string
    {
        return 'Module command.';
    }
}

PHP;
    }
}
