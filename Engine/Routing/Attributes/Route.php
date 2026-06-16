<?php

namespace Shift\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    public function __construct(
        public readonly string $method,
        public readonly string $path
    ) {
    }
}
