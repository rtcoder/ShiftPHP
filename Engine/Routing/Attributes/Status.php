<?php

namespace Engine\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Status
{
    public function __construct(public readonly int $code)
    {
    }
}
