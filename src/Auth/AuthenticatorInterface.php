<?php

namespace Shift\Auth;

use Shift\Request;

interface AuthenticatorInterface
{
    public function authenticate(Request $request): ?AuthenticatedUser;
}
