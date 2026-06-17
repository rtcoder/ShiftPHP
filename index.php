<?php
//error_reporting(E_ALL);
use Shift\App;
use Shift\Modules\ModuleLoader;
use Shift\Request;

error_reporting(-1);
ini_set('display_errors', 'Off');

require_once 'bootstrap.php';

// Create request instance and start application
$request = new Request();
$app = new App($request);
$modules = (new ModuleLoader())->load();
$modules->registerServices($app->getContainer());
$modules->registerRoutes($app->getRouter());
$modules->boot($app->getContainer());

$app->start();
