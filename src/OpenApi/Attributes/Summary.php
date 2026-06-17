<?php

namespace Shift\OpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Summary
{
    public function __construct(public readonly string $text)
    {
    }
}
