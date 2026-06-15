<?php

namespace Modules\Health\Commands;

use Engine\Console\Cli;
use Engine\Console\CommandInterface;
use Modules\Health\Services\HealthService;

class Health implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $health = new HealthService();

        foreach ($health->status() as $key => $value) {
            $cli->success($key . ': ' . $value);
        }
    }

    public function getHelp(): string
    {
        return 'Usage: php shift.php health';
    }

    public function getDescription(): string
    {
        return 'Show health module status.';
    }
}
