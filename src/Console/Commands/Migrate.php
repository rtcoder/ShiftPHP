<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Database\Database;
use Shift\Database\DatabaseConfig;
use Shift\Database\Migrations\MigrationRunner;

#[\Shift\Console\Attributes\Command('migrate', aliases: ['m'], group: 'database')]
class Migrate implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $ran = (new MigrationRunner(new Database(DatabaseConfig::fromEnv())))->migrate();

        if ($ran === []) {
            $cli->info('Nothing to migrate.');
            return;
        }

        foreach ($ran as $migration) {
            $cli->success('Migrated: ' . $migration);
        }
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift migrate';
    }

    public function getDescription(): string
    {
        return 'Run pending database migrations.';
    }
}
