<?php
error_reporting(-1);
ini_set('display_errors', 'Off');

require_once 'bootstrap.php';
require_once 'Engine/App.php';

Engine\App::setHelpers();
Engine\App::start();

