<?php

// Define application constants
use Shift\App;
use Shift\Config\EnvLoader;
use Shift\Error\ErrorHandler;

define('APP_ROOT', realpath(__DIR__ . '/'));
define('APP_PATH', realpath(__DIR__ . '/application/'));
define('VENDOR_PATH', realpath(APP_ROOT . '/vendor/'));
define('PUBLIC_PATH', realpath(APP_ROOT . '/public/'));

// Load Composer autoloader
require_once VENDOR_PATH . '/autoload.php';

// Load environment variables
(new EnvLoader())->load(APP_ROOT . '/.env');

// Register custom autoloader
spl_autoload_register(['Shift\App', 'autoload']);

// Register error handler
ErrorHandler::register();

// Set default timezone
date_default_timezone_set('UTC');

// Load helpers
App::setHelpers();
