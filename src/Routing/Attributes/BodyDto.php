<?php

namespace Shift\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class BodyDto
{
    public function __construct(public readonly ?string $class = null)
    {
    }
}
