<?php

namespace Engine;

/**
 * Class App
 * @package Engine
 */
final class App {
    /**
     * @var
     */
    public static $controller;
    public static $defaultController = 'index';
    public static $action;
    public static $defaultAction = 'index';


    /**
     * App constructor.
     */
    public function __construct() {
    }

    public static function start(): void {
        spl_autoload_register(array('\Engine\App', 'autoload'));

        static $run;
        if ($run === TRUE) return;

        Request::setup();
        $controller = ucfirst(Request::getController()) . 'Controller';

        self::$controller = new $controller();

        $class = new ReflectionClass(self::$controller);

        $method = $class->getMethod(URI::getAction());

        $method->invokeArgs(self::$controller, URI::getArguments());
        $run = TRUE;
    }


    /**
     * @param string $class_name
     */
    public static function autoload(string $class_name): void {
        $class = str_replace('_', '/',
            str_replace('\\', '/', $class_name)
        );

        if (empty($class)) {
            return;
        }

        $locations = [
            APP_PATH . 'application/controller/',
            APP_PATH . 'application/model/',
            APP_PATH,
        ];

        foreach ($locations as $location) {
            if (file_exists($location . $class . '.php')) {
                require_once($location . $class . '.php');
            }
        }

    }

    public static function setHelpers(): void {
        require_once 'Utils/helpers.php';
    }

}