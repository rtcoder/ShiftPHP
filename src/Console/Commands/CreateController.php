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

        $this->writeAndReport($path, $this->stub($module, $class));
    }

    public function getHelp(): string
    {
        return 'Usage: php shift.php create:controller --module={name} {ControllerName}';
    }

    public function getDescription(): string
    {
        return 'Create a module controller.';
    }

    private function stub(string $module, string $class): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Controllers;

use Shift\\Controller;
use Shift\\Response\\JsonResponse;

class {$class} extends Controller
{
    public function index(): JsonResponse
    {
        return \$this->json([
            'status' => 'ok',
        ]);
    }
}

PHP;
    }
}
