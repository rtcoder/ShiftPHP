#!/usr/bin/php
<?php

use Engine\App;
use Engine\Console\Shift;

require_once 'bootstrap.php';
App::setHelpers();
$console = new Shift($argv);
$console->run();
