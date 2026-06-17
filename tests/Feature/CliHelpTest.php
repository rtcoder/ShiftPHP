<?php

use Console\Commands\Help;

return [
    'help command lists available commands with descriptions' => function (): void {
        ob_start();
        (new Help())->execute();
        $output = ob_get_clean();

        assertStringContains('help', $output, 'Help list should include itself.');
        assertStringContains('Database', $output, 'Help list should group commands.');
        assertStringContains('Aliases', $output, 'Help list should include aliases column.');
        assertStringContains('migrate', $output, 'Help list should include migration commands.');
        assertStringContains('create:migration', $output, 'Help list should include migration generator.');
    },
    'help command shows a single command description and usage' => function (): void {
        ob_start();
        (new Help())->execute('migrate:status');
        $output = ob_get_clean();

        assertStringContains('migrate:status', $output, 'Command help should include normalized command name.');
        assertStringContains('Aliases: ms', $output, 'Command help should include aliases.');
        assertStringContains('Group: database', $output, 'Command help should include group.');
        assertStringContains('Usage: ./shift migrate:status', $output, 'Command help should include command usage.');
    },
];
