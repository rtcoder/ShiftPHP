<?php
define('APP_PATH', realpath($_SERVER['DOCUMENT_ROOT'] ? $_SERVER['DOCUMENT_ROOT'] : __DIR__ . '/'));
define('VENDOR_PATH', realpath(APP_PATH . '/vendor/'));
define('PUBLIC_PATH', realpath(APP_PATH . '/public/'));

require_once VENDOR_PATH . '/autoload.php';
