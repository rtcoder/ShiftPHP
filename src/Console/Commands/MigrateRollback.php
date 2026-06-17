<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Database\Database;
use Shift\Database\DatabaseConfig;
use Shift\Database\Migrations\MigrationRunner;

#[\Shift\Console\Attributes\Command('migrate:rollback', aliases: ['rollback'], group: 'database')]
class MigrateRollback implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $rolledBack = (new MigrationRunner(new Database(DatabaseConfig::fromEnv())))->rollback();

        if ($rolledBack === []) {
            $cli->info('Nothing to rollback.');
            return;
        }

        foreach ($rolledBack as $migration) {
            $cli->success('Rolled back: ' . $migration);
        }
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift migrate:rollback';
    }

    public function getDescription(): string
    {
        return 'Rollback the last migration batch.';
    }
}
