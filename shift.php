#!/usr/bin/php
<?php
require_once 'bootstrap.php';
\Engine\App::setHelpers();
$console = new \Engine\Console\Shift($argv);
$console->run();
