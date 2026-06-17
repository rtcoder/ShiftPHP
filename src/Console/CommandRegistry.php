<?php

namespace Shift\Console;

use Shift\Modules\ModuleLoader;

final class CommandRegistry
{
    /**
     * @param list<array{dir: string, namespace: string}>|null $mappings
     */
    public function __construct(private readonly ?array $mappings = null)
    {
    }

    public static function default(): self
    {
        return new self();
    }

    /**
     * @return array<string, class-string<CommandInterface>>
     */
    public function all(): array
    {
        $commands = [];

        foreach ($this->mappings() as $mapping) {
            if (!is_dir($mapping['dir'])) {
                continue;
            }

            foreach (glob(rtrim($mapping['dir'], '/') . '/*.php') ?: [] as $file) {
                $className = pathinfo($file, PATHINFO_FILENAME);
                require_once $file;

                $class = $mapping['namespace'] . $className;

                if (class_exists($class) && is_subclass_of($class, CommandInterface::class)) {
                    $commands[self::nameFromClass($className)] = $class;
                }
            }
        }

        ksort($commands);

        return $commands;
    }

    /**
     * @return class-string<CommandInterface>|null
     */
    public function find(string $command): ?string
    {
        return $this->all()[$this->normalize($command)] ?? null;
    }

    public function normalize(string $command): string
    {
        $command = trim($command);

        if ($command === '') {
            return '';
        }

        if (!preg_match('/[:\-_]/', $command) && preg_match('/[A-Z]/', $command)) {
            return self::nameFromClass($command);
        }

        $parts = preg_split('/[:\-_]/', $command, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_map(static fn (string $part): string => strtolower($part), $parts);

        return implode(':', $parts);
    }

    public static function nameFromClass(string $class): string
    {
        $parts = explode('\\', $class);
        $shortClass = end($parts) ?: $class;
        $commandParts = preg_split('/(?=[A-Z])/', $shortClass, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $commandParts = array_map(static fn (string $part): string => strtolower($part), $commandParts);

        return implode(':', $commandParts);
    }

    /**
     * @return list<array{dir: string, namespace: string}>
     */
    private function mappings(): array
    {
        if ($this->mappings !== null) {
            return $this->mappings;
        }

        return array_merge(
            [
                [
                    'dir' => APP_PATH . '/console/',
                    'namespace' => 'AppConsole\\Commands\\',
                ],
                [
                    'dir' => APP_ROOT . '/src/Console/Commands/',
                    'namespace' => 'Console\\Commands\\',
                ],
            ],
            (new ModuleLoader())->load()->getCommandMappings()
        );
    }
}
