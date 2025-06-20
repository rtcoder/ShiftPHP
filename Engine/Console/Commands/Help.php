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

    private $mappings = [
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
    public function execute(...$args): void
    {
        $commandName = $args[0] ?? '';

        if ($commandName) {
            $this->displayHelpForCommand($commandName);
        } else {
            $this->displayFullHelp();
        }
    }

    private function displayHelpForCommand($command)
    {

        $found = false;

        foreach ($this->mappings as $mapping) {
            if (!$found && file_exists($mapping['dir'] . $command . '.php')) {
                require_once($mapping['dir'] . $command . '.php');
                $found = $mapping['namespace'] . $command;
            }
        }
    }

    private function displayFullHelp()
    {

    }
}
