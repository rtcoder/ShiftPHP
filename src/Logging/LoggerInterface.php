<?php

namespace Shift\Logging;

interface LoggerInterface
{
    public function log(string $level, string $message, array $context = []): void;
}
