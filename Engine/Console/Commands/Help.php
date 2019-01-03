<?php
/**
 * Created by PhpStorm.
 * User: dawidjez
 * Date: 14/12/2018
 * Time: 12:50
 */

namespace Console\Commands;


use Engine\Console\CommandInterface;

class Help implements CommandInterface {

    public function execute(...$args): void {

        var_dump($args);

        $mappings = [
            [
                'dir' => APP_PATH . '/console/',
                'namespace' => 'AppConsole\\Commands\\'
            ],
            [
                'dir' => APP_ROOT . '/Engine/Console/Commands/',
                'namespace' => 'Console\\Commands\\'
            ],
        ];
        $found = false;
        foreach ($mappings as $mapping) {
            if (!$found && file_exists($mapping['dir'] . $commandName . '.php')) {
                require_once($mapping['dir'] . $commandName . '.php');
                $found = $mapping['namespace'] . $commandName;
            }
        }
    }
}
