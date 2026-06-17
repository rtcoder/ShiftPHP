<?php

namespace Shift\Logging;

final class NullLogger implements LoggerInterface
{
    public function log(string $level, string $message, array $context = []): void
    {
    }
}
