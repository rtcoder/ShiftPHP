<?php
//error_reporting(E_ALL);
error_reporting(-1);
ini_set('display_errors', 'Off');

require_once 'bootstrap.php';

// Create request instance and start application
$request = new Engine\Request();
$app = new Engine\App($request);
$app->start();

