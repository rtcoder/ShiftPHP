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
use Shift\Console\CommandRegistry;

class Help implements CommandInterface
{
    public function __construct(private readonly CommandRegistry $registry = new CommandRegistry())
    {
    }

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
        $class = $this->registry->find($command);

        if ($class === null) {
            $cli->error('Command not found: ' . $command);
            return;
        }

        $instance = new $class();
        $cli->info(CommandRegistry::nameFromClass($class));
        $cli->debug($instance->getDescription());
        $cli->debug($instance->getHelp());
    }

    private function displayFullHelp(): void
    {
        $cli = new Cli();
        $rows = [];

        foreach ($this->registry->all() as $command => $class) {
            $instance = new $class();
            $rows[] = [
                $command,
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
}
