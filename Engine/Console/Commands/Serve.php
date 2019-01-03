<?php
/**
 * Created by PhpStorm.
 * User: dawidjez
 * Date: 14/12/2018
 * Time: 12:50
 */

namespace Console\Commands;


use Engine\Console\CommandInterface;

class Serve implements CommandInterface {

    public function execute(...$args): void {
        $host = 'localhost:8000';
        if (count($args)) {
            if (isset($args[0])) {
                $host = $args[0];
            }
        }

        $command = $this->getOpenCommand() . ' http://' . $host . ';php -S ' . $host;

        exec($command);
    }

    private final function getOpenCommand(): string {
        $command = 'open';

        switch (php_uname('s')) {
            case 'Linux':
                $command = 'xdg-open';
                break;
            case 'Mac':
                $command = 'open';
                break;
        }

        return $command;
    }
}
