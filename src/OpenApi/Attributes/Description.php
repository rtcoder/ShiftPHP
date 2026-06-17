<?php

namespace Shift\OpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class Description
{
    public function __construct(public readonly string $text)
    {
    }
}
