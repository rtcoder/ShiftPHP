<?php

namespace Shift\Logging;

use JsonException;

final class JsonFileLogger implements LoggerInterface
{
    public function __construct(private readonly string $path)
    {
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $directory = dirname($this->path);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $record = [
            'timestamp' => date(DATE_ATOM),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        try {
            $line = json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        } catch (JsonException) {
            $line = json_encode([
                'timestamp' => date(DATE_ATOM),
                'level' => LogLevel::ERROR,
                'message' => 'Log record could not be encoded.',
            ], JSON_THROW_ON_ERROR) . PHP_EOL;
        }

        file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }
}
