#!/usr/bin/php
<?php

use Shift\App;
use Shift\Console\Shift;

require_once 'bootstrap.php';
App::setHelpers();
$console = new Shift($argv);
$console->run();
