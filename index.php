<?php
//error_reporting(E_ALL);
use Engine\App;
use Engine\Request;

error_reporting(-1);
ini_set('display_errors', 'Off');

require_once 'bootstrap.php';

// Create request instance and start application
$request = new Request();
$app = new App($request);
$app->start();
