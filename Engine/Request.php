<?php

namespace Engine;

/**
 * Class Request
 * @package Engine
 */
class Request {


    /**
     * @var
     */
    protected static $array;
    /**
     * @var
     */
    protected static $controller;
    /**
     * @var
     */
    protected static $action;
    /**
     * @var
     */
    protected static $arguments;
    /**
     * @var
     */
    protected static $path;

    /**
     *
     */
    public static function setup() {

        self::$path = $_SERVER['REQUEST_URI'];


        self::$array = explode('/', trim(self::$path, '/'));

        self::$controller = self::$array[0] ?? App::$defaultController;
        self::$action = self::$array[1] ?? App::$defaultAction;
        var_dump(Request::getArray());
    }

    /**
     * @return array
     */
    public static function getArray(): array {
        return self::$array;
    }

    /**
     * @param array $array
     */
    public static function setArray(array $array): void {
        self::$array = $array;
    }

    /**
     * @return string
     */
    public static function getController(): string {
        return self::$controller;
    }

    /**
     * @param string $controller
     */
    public static function setController(string $controller): void {
        self::$controller = $controller;
    }

    /**
     * @return string
     */
    public static function getAction(): string {
        return self::$action;
    }

    /**
     * @param string $action
     */
    public static function setAction($action): void {
        self::$action = $action;
    }

    /**
     * @return array
     */
    public static function getArguments(): array {
        return self::$arguments;
    }

    /**
     * @param string $arguments
     */
    public static function setArguments($arguments): void {
        self::$arguments = $arguments;
    }

    /**
     * @return string
     */
    public static function getPath(): string {
        return self::$path;
    }

    /**
     * @param string $path
     */
    public static function setPath($path): void {
        self::$path = $path;
    }
}