<?php
/**
 * Created by PhpStorm.
 * User: dawidjez
 * Date: 14/12/2018
 * Time: 11:57
 */

namespace Engine\Console;

use Engine\Modules\ModuleLoader;
use ReflectionClass;
use ReflectionException;

class Shift
{
    protected string $_description = 'xd';
    private array $_args = [];

    public function __construct(array $argv)
    {
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

    /**
     * @throws ReflectionException
     * @return void
     */
    public function run(): void
    {
        $cli = new Cli();
        if (count($this->_args) < 2) {
            $cli->error('Shift CLI needs at least one parameter');
            exit();
        }
        $commandName = $this->normalizeCommandName($this->_args[1]);

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
        $mappings = array_merge(
            $mappings,
            (new ModuleLoader())->load()->getCommandMappings()
        );
        $found = false;
        foreach ($mappings as $mapping) {
            if (!$found && file_exists($mapping['dir'] . $commandName . '.php')) {
                require_once($mapping['dir'] . $commandName . '.php');
                $found = $mapping['namespace'] . $commandName;
            }
        }
        if (!$found) {
            $cli->error('Command ' . $commandName . ' not found');
            exit();
        }

        $cl = new $found();
        $class = new ReflectionClass($cl);
        $method = $class->getMethod('execute');
        $args = array_slice($this->_args, 2, count($this->_args));
        $method->invokeArgs($cl, $args);
    }

    private function normalizeCommandName(string $command): string
    {
        $parts = preg_split('/[:\-_]/', $command) ?: [];
        $parts = array_map(static fn (string $part): string => ucfirst($part), $parts);

        return implode('', $parts);
    }

}
