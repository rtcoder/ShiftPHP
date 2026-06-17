<?php

namespace Shift\Console\Generator;

final class NameFormatter
{
    public static function className(string $name, string $suffix = ''): string
    {
        $name = str_replace('\\', '/', $name);
        $name = basename($name);
        $name = self::studly($name);

        if ($suffix !== '' && !str_ends_with($name, $suffix)) {
            return $name . $suffix;
        }

        return $name;
    }

    public static function moduleName(string $name): string
    {
        return self::studly($name);
    }

    public static function commandName(string $className): string
    {
        $parts = preg_split('/(?=[A-Z])/', $className, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_map(static fn (string $part): string => strtolower($part), $parts);

        return implode(':', $parts);
    }

    public static function slug(string $name): string
    {
        $parts = preg_split('/[^a-zA-Z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_map(static fn (string $part): string => strtolower($part), $parts);

        return implode('-', $parts);
    }

    private static function studly(string $name): string
    {
        $parts = preg_split('/[^a-zA-Z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_map(static fn (string $part): string => ucfirst($part), $parts);

        return implode('', $parts);
    }
}
