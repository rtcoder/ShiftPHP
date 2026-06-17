<?php

use Shift\Logging\JsonFileLogger;

return [
    'json file logger writes structured records' => function (): void {
        $root = sys_get_temp_dir() . '/shift-logs-' . bin2hex(random_bytes(6));
        $path = $root . '/shift.log';

        try {
            (new JsonFileLogger($path))->log('info', 'Structured event', [
                'request' => [
                    'path' => '/health',
                ],
            ]);

            assertFileExists($path, 'Log file should be created.');

            $lines = file($path, FILE_IGNORE_NEW_LINES);
            $record = json_decode($lines[0] ?? '', true);

            assertSameValue('info', $record['level'] ?? null, 'Log record should contain level.');
            assertSameValue('Structured event', $record['message'] ?? null, 'Log record should contain message.');
            assertSameValue('/health', $record['context']['request']['path'] ?? null, 'Log record should contain structured context.');
        } finally {
            removeDirectory($root);
        }
    },
];
