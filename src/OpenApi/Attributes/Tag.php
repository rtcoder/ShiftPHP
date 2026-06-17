<?php

namespace Shift\OpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Tag
{
    public function __construct(public readonly string $name)
    {
    }
}
