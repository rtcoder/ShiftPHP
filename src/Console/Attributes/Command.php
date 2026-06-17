<?php

namespace Shift\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Command
{
    /**
     * @param list<string> $aliases
     */
    public function __construct(
        public readonly string $name,
        public readonly array $aliases = [],
        public readonly string $group = 'general'
    ) {
    }
}
