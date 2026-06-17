<?php
/**
 * Created by PhpStorm.
 * User: dawidjez
 * Date: 14/12/2018
 * Time: 11:57
 */

namespace Shift\Console;

class Shift
{
    private array $_args = [];

    public function __construct(
        array $argv,
        private readonly CommandRegistry $registry = new CommandRegistry()
    ) {
        $this->setArgs($argv);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getArg(string $name): mixed
    {
        return $this->_args[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->_args;
    }

    /**
     * @param array $args
     */
    public function setArgs(array $args): void
    {
        $this->_args = $args;
    }

    public function run(): void
    {
        $cli = new Cli();

        if (count($this->_args) < 2) {
            $cli->error('Usage: ./shift help');
            exit();
        }

        $command = $this->registry->find($this->_args[1]);

        if ($command === null) {
            $cli->error('Command ' . $this->_args[1] . ' not found');
            exit();
        }

        $instance = new $command();
        $args = array_slice($this->_args, 2, count($this->_args));
        $instance->execute(...$args);
    }
}
