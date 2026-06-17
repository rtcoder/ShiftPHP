<?php

namespace Shift\Auth;

use Shift\Request;

interface AuthorizerInterface
{
    public function authorize(AuthenticatedUser $user, Request $request, ?string $ability = null): bool;
}
