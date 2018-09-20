<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 29.07.18
 * Time: 15:11
 */

namespace Tools;


final class Config {
    private static $instance;

    private static $_configs = [];

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public static function __get(?string $name) {

        if($name){
            if(array_key_exists($name, self::$_configs)){
                return self::$_configs[$name];
            }
        }

        return self::$_configs;
    }

}