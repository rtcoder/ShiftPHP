<?php

namespace Shift\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Patch extends Route
{
    public function __construct(string $path)
    {
        parent::__construct('PATCH', $path);
    }
}
