<?php
error_reporting(E_ALL);
define('APP_PATH', realpath($_SERVER['DOCUMENT_ROOT'] . '/'));
define('VENDOR_PATH', realpath(APP_PATH . '/vendor/'));
define('PUBLIC_PATH', realpath(APP_PATH . '/public/'));

require_once VENDOR_PATH . '/autoload.php';
