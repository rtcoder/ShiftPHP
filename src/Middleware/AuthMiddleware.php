<?php

namespace Shift\Middleware;

use Shift\Auth\AuthenticatedUser;
use Shift\Auth\AuthenticatorInterface;
use Shift\Request;
use Shift\Response\JsonResponse;
use Shift\Response\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AuthenticatorInterface $authenticator)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $this->authenticator->authenticate($request);

        if (!$user instanceof AuthenticatedUser) {
            return JsonResponse::error('Unauthorized', 401);
        }

        $request->setAttribute(AuthenticatedUser::class, $user);
        $request->setAttribute('user', $user);

        return $next($request);
    }
}
