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

#[\Shift\Console\Attributes\Command('help', aliases: ['h'], group: 'core')]
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
        $definition = $this->registry->findDefinition($command);

        if ($definition === null) {
            $cli->error('Command not found: ' . $command);
            return;
        }

        $instance = $definition->instantiate();
        $cli->info($definition->name);
        $cli->debug('Group: ' . $definition->group);

        if ($definition->aliases !== []) {
            $cli->debug('Aliases: ' . implode(', ', $definition->aliases));
        }

        $cli->debug($instance->getDescription());
        $cli->debug($instance->getHelp());
    }

    private function displayFullHelp(): void
    {
        $cli = new Cli();
        $groups = [];

        foreach ($this->registry->definitions() as $definition) {
            $instance = $definition->instantiate();
            $groups[$definition->group][] = [
                $definition->name,
                $definition->aliases === [] ? '' : implode(', ', $definition->aliases),
                $instance->getDescription(),
            ];
        }

        ksort($groups);

        foreach ($groups as $group => $rows) {
            usort($rows, static fn (array $left, array $right): int => strcmp($left[0], $right[0]));

            $cli->info(ucfirst($group));
            $cli->table(['Command', 'Aliases', 'Description'], $rows);
        }
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
