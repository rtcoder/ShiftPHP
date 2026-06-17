<?php

namespace Shift\Config;

final class Env
{
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        return $default;
    }

    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }
}
