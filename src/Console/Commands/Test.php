<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;

#[\Shift\Console\Attributes\Command('test', aliases: ['t'], group: 'diagnostics')]
class Test implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $testFile = APP_ROOT . '/tests/ApiCoreTest.php';

        if (!is_file($testFile)) {
            $cli->error('Test runner not found: ' . $testFile);
            return;
        }

        $command = 'XDEBUG_MODE=off ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($testFile);
        passthru($command, $exitCode);

        if ($exitCode === 0) {
            $cli->success('Test suite passed.');
            return;
        }

        $cli->error('Test suite failed.');
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift test';
    }

    public function getDescription(): string
    {
        return 'Run the project test suite.';
    }
}
