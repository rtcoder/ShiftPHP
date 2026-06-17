<?php

namespace Shift\Logging;

use Shift\Config\Env;

final class LoggerFactory
{
    public static function fromEnv(): LoggerInterface
    {
        if (!self::enabled()) {
            return new NullLogger();
        }

        return new JsonFileLogger(self::path((string) Env::get('LOG_PATH', 'storage/logs/shift.log')));
    }

    private static function enabled(): bool
    {
        return in_array(strtolower((string) Env::get('LOG_ENABLED', 'false')), ['1', 'true', 'yes', 'on'], true);
    }

    private static function path(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return APP_ROOT . '/' . ltrim($path, '/');
    }
}
