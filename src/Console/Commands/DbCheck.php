<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Database\Database;
use Shift\Database\DatabaseConfig;
use Shift\Database\DatabaseException;
use Throwable;

#[\Shift\Console\Attributes\Command('db:check', group: 'diagnostics')]
class DbCheck implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $config = DatabaseConfig::fromEnv();

        $cli->info('Checking database connection...');
        $cli->debug('Driver: ' . $config->driver);
        $cli->debug('Database: ' . ($config->database !== '' ? $config->database : '(not configured)'));

        try {
            (new Database($config))->pdo();
            $cli->success('Database connection OK.');
        } catch (DatabaseException | Throwable $exception) {
            $cli->error('Database connection failed.');
            $cli->debug($exception->getMessage());
        }
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift db:check';
    }

    public function getDescription(): string
    {
        return 'Check database connectivity from environment configuration.';
    }
}
