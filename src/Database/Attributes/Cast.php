<?php

namespace Shift\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Cast
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $format = null
    ) {
    }
}
