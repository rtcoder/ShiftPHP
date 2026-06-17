<?php

namespace Shift\OpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Response
{
    public function __construct(
        public readonly int $status,
        public readonly string $description = 'Response',
        public readonly ?string $type = 'object'
    ) {
    }
}
