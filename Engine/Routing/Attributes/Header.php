<?php

namespace Engine\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Header
{
    public function __construct(
        public readonly string $name,
        public readonly string $value
    ) {
    }
}
