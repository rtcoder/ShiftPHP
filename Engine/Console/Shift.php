<?php
/**
 * Created by PhpStorm.
 * User: dawidjez
 * Date: 14/12/2018
 * Time: 11:57
 */

namespace Engine\Console;


use Grabower\CliTypo\CliTypo;
use ReflectionClass;

class Shift
{
    protected $_description = 'xd';
    private $_args = [];

    public function __construct(array $argv)
    {
        $this->setArgs($argv);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getArg(string $name)
    {
        return isset($this->_args[$name]) ? $this->_args[$name] : null;
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
        $cliTypo = new CliTypo();
        if (count($this->_args) < 2) {
            $cliTypo->alert()->error('Shift CLI needs at least one parameter');
            exit();
        }
        $commandName = ucfirst($this->_args[1]);
        $cliTypo->text()->write($commandName);

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
        if (!$found) {
            $cliTypo->alert()->error('Command ' . $commandName . ' not found');
            exit();
        }

        $cl = new $found();
        $class = new ReflectionClass($cl);
        $method = $class->getMethod('execute');
        $args = array_slice($this->_args, 2, count($this->_args));
        $method->invokeArgs($cl, $args);
    }

}
