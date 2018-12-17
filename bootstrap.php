<?php
define('APP_ROOT', realpath(__DIR__ . '/'));
define('APP_PATH', realpath(__DIR__ . '/application/'));
define('VENDOR_PATH', realpath(APP_ROOT . '/vendor/'));
define('PUBLIC_PATH', realpath(APP_ROOT . '/public/'));

require_once VENDOR_PATH . '/autoload.php';
