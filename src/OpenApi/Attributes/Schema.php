<?php

namespace Shift\OpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class Schema
{
    /**
     * @param list<string> $enum
     */
    public function __construct(
        public readonly ?string $type = null,
        public readonly ?string $format = null,
        public readonly ?string $description = null,
        public readonly ?string $itemsType = null,
        public readonly array $enum = [],
        public readonly ?bool $required = null,
        public readonly ?bool $nullable = null
    ) {
    }
}
