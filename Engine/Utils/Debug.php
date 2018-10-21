<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 31.05.18
 * Time: 01:53
 */

namespace Engine\Utils;


class Debug {

    /**
     *
     */
    public function __invoke() {
        return self::dd(func_get_args());
    }

    public static function dd(): void {
        echo '<pre>';
        var_dump(func_get_args());
//        Debug::print(func_get_args());
        exit;

    }

    private static function print($var, $key = null): void {
        echo '<div style="border: 1px solid red;padding-left: 5px;">';
//
        if (is_array($var)) {
            echo gettype($var) . ' (' . count($var) . ')';
            foreach ($var as $k => $item) {

                Debug::print($item, $k);
            }
        } else {
            if (!is_null($key)) {
                echo $key . ': ';
            }
            echo gettype($var) . ' ' . $var;
        }
//
        echo '</div>';
    }
}