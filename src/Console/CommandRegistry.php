<?php

namespace Shift\Console;

use ReflectionClass;
use Shift\Console\Attributes\Command;
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
        return array_map(
            static fn (CommandDefinition $definition): string => $definition->class,
            $this->definitions()
        );
    }

    /**
     * @return array<string, CommandDefinition>
     */
    public function definitions(): array
    {
        $definitions = [];

        foreach ($this->mappings() as $mapping) {
            if (!is_dir($mapping['dir'])) {
                continue;
            }

            foreach (glob(rtrim($mapping['dir'], '/') . '/*.php') ?: [] as $file) {
                $className = pathinfo($file, PATHINFO_FILENAME);
                require_once $file;

                $class = $mapping['namespace'] . $className;

                if (class_exists($class) && is_subclass_of($class, CommandInterface::class)) {
                    $definition = $this->definitionFor($class, $className);
                    $definitions[$definition->name] = $definition;
                }
            }
        }

        ksort($definitions);

        return $definitions;
    }

    /**
     * @return class-string<CommandInterface>|null
     */
    public function find(string $command): ?string
    {
        return $this->findDefinition($command)?->class;
    }

    public function findDefinition(string $command): ?CommandDefinition
    {
        $normalized = $this->normalize($command);

        foreach ($this->definitions() as $definition) {
            if ($definition->name === $normalized || in_array($normalized, $definition->aliases, true)) {
                return $definition;
            }
        }

        return null;
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
     * @param class-string<CommandInterface> $class
     */
    private function definitionFor(string $class, string $fallbackClassName): CommandDefinition
    {
        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(Command::class);

        if ($attributes !== []) {
            /** @var Command $attribute */
            $attribute = $attributes[0]->newInstance();

            return new CommandDefinition(
                $this->normalize($attribute->name),
                $class,
                $this->normalizeList($attribute->aliases),
                $attribute->group
            );
        }

        return new CommandDefinition(self::nameFromClass($fallbackClassName), $class);
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private function normalizeList(array $values): array
    {
        return array_values(array_filter(array_map(
            fn (string $value): string => $this->normalize($value),
            $values
        )));
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
