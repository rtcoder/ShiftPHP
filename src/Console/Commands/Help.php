<?php
/**
 * Created by PhpStorm.
 * User: dawidjez
 * Date: 14/12/2018
 * Time: 12:50
 */

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Modules\ModuleLoader;

class Help implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $commandName = $args[0] ?? null;

        if (is_string($commandName) && $commandName !== '') {
            $this->displayHelpForCommand($commandName);
            return;
        }

        $this->displayFullHelp();
    }

    private function displayHelpForCommand(string $command): void
    {
        $cli = new Cli();
        $class = $this->findCommandClass($this->normalizeCommandName($command));

        if ($class === null) {
            $cli->error('Command not found: ' . $command);
            return;
        }

        $instance = new $class();
        $cli->info($this->classToCommand($this->shortClass($class)));
        $cli->debug($instance->getDescription());
        $cli->debug($instance->getHelp());
    }

    private function displayFullHelp(): void
    {
        $cli = new Cli();
        $rows = [];

        foreach ($this->commandClasses() as $className => $class) {
            $instance = new $class();
            $rows[] = [
                $this->classToCommand($className),
                $instance->getDescription(),
            ];
        }

        usort($rows, static fn (array $left, array $right): int => strcmp($left[0], $right[0]));

        $cli->table(['Command', 'Description'], $rows);
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift help [command]';
    }

    public function getDescription(): string
    {
        return 'Show available commands.';
    }

    private function findCommandClass(string $className): ?string
    {
        return $this->commandClasses()[$className] ?? null;
    }

    /**
     * @return array<string, class-string<CommandInterface>>
     */
    private function commandClasses(): array
    {
        $classes = [];

        foreach ($this->mappings() as $mapping) {
            if (!is_dir($mapping['dir'])) {
                continue;
            }

            foreach (glob($mapping['dir'] . '*.php') ?: [] as $file) {
                $className = pathinfo($file, PATHINFO_FILENAME);
                require_once $file;
                $class = $mapping['namespace'] . $className;

                if (class_exists($class) && is_subclass_of($class, CommandInterface::class)) {
                    $classes[$className] = $class;
                }
            }
        }

        return $classes;
    }

    /**
     * @return list<array{dir: string, namespace: string}>
     */
    private function mappings(): array
    {
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

    private function normalizeCommandName(string $command): string
    {
        $parts = preg_split('/[:\-_]/', $command) ?: [];
        $parts = array_map(static fn (string $part): string => ucfirst($part), $parts);

        return implode('', $parts);
    }

    private function classToCommand(string $class): string
    {
        $parts = preg_split('/(?=[A-Z])/', $class, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_map(static fn (string $part): string => strtolower($part), $parts);

        return implode(':', $parts);
    }

    private function shortClass(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts) ?: $class;
    }
}
