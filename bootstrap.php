<?php

// Define application constants
use Shift\App;
use Shift\Error\ErrorHandler;

define('APP_ROOT', realpath(__DIR__ . '/'));
define('APP_PATH', realpath(__DIR__ . '/application/'));
define('VENDOR_PATH', realpath(APP_ROOT . '/vendor/'));
define('PUBLIC_PATH', realpath(APP_ROOT . '/public/'));

// Load Composer autoloader
require_once VENDOR_PATH . '/autoload.php';

// Register custom autoloader
spl_autoload_register(['Shift\App', 'autoload']);

// Register error handler
ErrorHandler::register();

// Set default timezone
date_default_timezone_set('UTC');

// Load helpers
App::setHelpers();
