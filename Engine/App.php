<?php

namespace Engine;

use ReflectionClass;

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

    /**
     * @throws \ReflectionException
     */
    public static function start(): void {
        static $run;
        if ($run === TRUE) return;

        Request::setup();
        $controller = 'Controllers\\' . ucfirst(Request::getController()) . 'Controller';

        self::$controller = new $controller();

        $class = new ReflectionClass(self::$controller);

        $method = $class->getMethod(Request::getAction());

        $method->invokeArgs(self::$controller, Request::getArguments());
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