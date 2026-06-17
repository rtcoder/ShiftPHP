<?php

namespace Shift\Auth;

final class AuthenticatedUser
{
    public function __construct(
        public readonly string $id,
        public readonly array $attributes = []
    ) {
    }
}
