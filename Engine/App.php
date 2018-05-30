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

        $controller = Request::getController() . '_controller';
        //utworzenie kontrolera
        self::$controller = new $controller();
        // tworzymy obiekt refleksji klasy
        $class = new ReflectionClass(self::$controller);
        //pobieramy metodę
        $method = $class->getMethod(URI::getAction());
        //wykonanie metody z argumentami URI
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
        require_once(APP_PATH . $class . '.php');

    }


}