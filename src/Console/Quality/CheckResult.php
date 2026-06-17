<?php

namespace Shift\Console\Quality;

final class CheckResult
{
    public function __construct(
        public readonly string $name,
        public readonly string $status,
        public readonly string $details
    ) {
    }

    public static function ok(string $name, string $details): self
    {
        return new self($name, 'ok', $details);
    }

    public static function fail(string $name, string $details): self
    {
        return new self($name, 'fail', $details);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public function toRow(): array
    {
        return [$this->name, $this->status, $this->details];
    }

    public function passed(): bool
    {
        return $this->status !== 'fail';
    }
}
