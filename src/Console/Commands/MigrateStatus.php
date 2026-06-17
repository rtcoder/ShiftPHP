<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Database\Database;
use Shift\Database\DatabaseConfig;
use Shift\Database\Migrations\MigrationRunner;

class MigrateStatus implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $rows = [];

        foreach ((new MigrationRunner(new Database(DatabaseConfig::fromEnv())))->status() as $migration) {
            $rows[] = [
                $migration['name'],
                $migration['ran'] ? 'yes' : 'no',
                $migration['batch'] === null ? '' : (string) $migration['batch'],
            ];
        }

        if ($rows === []) {
            $cli->warning('No migrations found.');
            return;
        }

        $cli->table(['Migration', 'Ran', 'Batch'], $rows);
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift migrate:status';
    }

    public function getDescription(): string
    {
        return 'Show database migration status.';
    }
}
