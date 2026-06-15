<?php

namespace Engine\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class QueryParam
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly mixed $default = null
    ) {
    }
}
