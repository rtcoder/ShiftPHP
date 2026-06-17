<?php

use Console\Commands\Help;

return [
    'help command lists available commands with descriptions' => function (): void {
        ob_start();
        (new Help())->execute();
        $output = ob_get_clean();

        assertStringContains('help', $output, 'Help list should include itself.');
        assertStringContains('migrate', $output, 'Help list should include migration commands.');
        assertStringContains('create:migration', $output, 'Help list should include migration generator.');
    },
    'help command shows a single command description and usage' => function (): void {
        ob_start();
        (new Help())->execute('migrate:status');
        $output = ob_get_clean();

        assertStringContains('migrate:status', $output, 'Command help should include normalized command name.');
        assertStringContains('Usage: ./shift migrate:status', $output, 'Command help should include command usage.');
    },
];
