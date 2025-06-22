<?php
/**
 * Created by PhpStorm.
 * User: dawidjez
 * Date: 14/12/2018
 * Time: 12:50
 */

namespace Console\Commands;

use Engine\Console\CommandInterface;

class Help implements CommandInterface
{
    /** @var array<array{dir: string, namespace: string}> */
    private array $mappings = [
        [
            'dir' => APP_PATH . '/console/',
            'namespace' => 'AppConsole\\Commands\\'
        ],
        [
            'dir' => APP_ROOT . '/Engine/Console/Commands/',
            'namespace' => 'Console\\Commands\\'
        ],
    ];

    /**
     * @param mixed ...$args
     */
    public function execute(mixed ...$args): void
    {
        $commandName = $args[0] ?? '';

        if ($commandName) {
            $this->displayHelpForCommand($commandName);
        } else {
            $this->displayFullHelp();
        }
    }

    /**
     * @param string $command
     */
    private function displayHelpForCommand(string $command): void
    {
        $found = false;

        foreach ($this->mappings as $mapping) {
            if (!$found && file_exists($mapping['dir'] . $command . '.php')) {
                require_once($mapping['dir'] . $command . '.php');
                $found = $mapping['namespace'] . $command;
            }
        }
    }

    private function displayFullHelp(): void
    {
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        // TODO: Implement getHelp() method.
        return '';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        // TODO: Implement getDescription() method.
        return '';
    }
}
