<?php

use Console\Commands\MigrateStatus;
use Shift\Console\CommandRegistry;
use Shift\Console\Shift;

return [
    'command registry discovers built-in and module commands' => function (): void {
        $registry = CommandRegistry::default();
        $commands = $registry->all();
        $definitions = $registry->definitions();

        assertSameValue(MigrateStatus::class, $commands['migrate:status'] ?? null, 'Registry should expose built-in commands by CLI name.');
        assertStringContains('Modules\\Health\\Commands\\Health', $commands['health'] ?? '', 'Registry should expose module commands.');
        assertSameValue('database', $definitions['migrate:status']->group ?? null, 'Registry should expose command groups.');
        assertSameValue(['ms'], $definitions['migrate:status']->aliases ?? null, 'Registry should expose command aliases.');
    },
    'command registry normalizes command names' => function (): void {
        $registry = CommandRegistry::default();

        assertSameValue(MigrateStatus::class, $registry->find('migrate-status'), 'Dash command names should resolve.');
        assertSameValue(MigrateStatus::class, $registry->find('migrate_status'), 'Underscore command names should resolve.');
        assertSameValue(MigrateStatus::class, $registry->find('MigrateStatus'), 'Class-like command names should resolve.');
        assertSameValue(MigrateStatus::class, $registry->find('ms'), 'Aliases should resolve.');
    },
    'cli dispatcher runs commands through the registry' => function (): void {
        ob_start();
        (new Shift(['shift', 'help', 'migrate:status']))->run();
        $output = ob_get_clean();

        assertStringContains('migrate:status', $output, 'Dispatcher should run resolved commands.');
        assertStringContains('Usage: ./shift migrate:status', $output, 'Dispatcher should pass command arguments.');
    },
];
