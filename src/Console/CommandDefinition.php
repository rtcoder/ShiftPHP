<?php

namespace Shift\Console;

final class CommandDefinition
{
    /**
     * @param class-string<CommandInterface> $class
     * @param list<string> $aliases
     */
    public function __construct(
        public readonly string $name,
        public readonly string $class,
        public readonly array $aliases = [],
        public readonly string $group = 'general'
    ) {
    }

    public function instantiate(): CommandInterface
    {
        return new $this->class();
    }
}
