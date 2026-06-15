<?php

namespace Engine\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class PathParam
{
    public function __construct(public readonly ?string $name = null)
    {
    }
}
