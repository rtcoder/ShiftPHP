<?php

namespace Shift\OpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Security
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public readonly string $name,
        public readonly array $scopes = []
    ) {
    }
}
