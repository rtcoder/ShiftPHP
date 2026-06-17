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

        $this->writeAndReport($path, $this->stub($module, $class));
    }

    public function getHelp(): string
    {
        return 'Usage: php shift.php create:middleware --module={name} {MiddlewareName}';
    }

    public function getDescription(): string
    {
        return 'Create a module middleware.';
    }

    private function stub(string $module, string $class): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Middleware;

use Shift\\Middleware\\MiddlewareInterface;
use Shift\\Request;
use Shift\\Response\\Response;

class {$class} implements MiddlewareInterface
{
    public function handle(Request \$request, callable \$next): Response
    {
        return \$next(\$request);
    }
}

PHP;
    }
}
