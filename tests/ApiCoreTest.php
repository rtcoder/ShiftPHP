<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/TestSupport.php';

foreach (glob(__DIR__ . '/Fixtures/*.php') ?: [] as $fixtureFile) {
    require_once $fixtureFile;
}

$tests = [];

foreach (glob(__DIR__ . '/Feature/*Test.php') ?: [] as $testFile) {
    $loadedTests = require $testFile;

    if (!is_array($loadedTests)) {
        throw new RuntimeException("Test file {$testFile} must return an array.");
    }

    $tests = array_merge($tests, $loadedTests);
}

$failed = 0;

foreach ($tests as $name => $test) {
    try {
        $test();
        echo "[PASS] {$name}" . PHP_EOL;
    } catch (Throwable $exception) {
        $failed++;
        echo "[FAIL] {$name}" . PHP_EOL;
        echo $exception->getMessage() . PHP_EOL;
    }
}

if ($failed > 0) {
    exit(1);
}

echo count($tests) . ' tests passed.' . PHP_EOL;
