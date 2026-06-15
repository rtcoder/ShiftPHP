<?php

namespace Engine\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Body
{
    public function __construct(public readonly ?string $key = null)
    {
    }
}
