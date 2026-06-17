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

        $this->writeAndReport($path, $this->stub($module, $class));
    }

    public function getHelp(): string
    {
        return 'Usage: php shift.php create:dto --module={name} {DtoName}';
    }

    public function getDescription(): string
    {
        return 'Create a module request DTO.';
    }

    private function stub(string $module, string $class): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Dto;

use Shift\\Validation\\RequestDto;

class {$class} extends RequestDto
{
    public function __construct()
    {
    }

    public static function rules(): array
    {
        return [
        ];
    }
}

PHP;
    }
}
