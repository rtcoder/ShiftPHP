<?php

namespace Shift\Middleware;

use Shift\Auth\AuthenticatedUser;
use Shift\Auth\AuthorizerInterface;
use Shift\Request;
use Shift\Response\JsonResponse;
use Shift\Response\Response;

class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthorizerInterface $authorizer,
        private readonly ?string $ability = null
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $request->getAttribute(AuthenticatedUser::class) ?? $request->getAttribute('user');

        if (!$user instanceof AuthenticatedUser) {
            return JsonResponse::error('Unauthorized', 401);
        }

        if (!$this->authorizer->authorize($user, $request, $this->ability)) {
            return JsonResponse::error('Forbidden', 403);
        }

        return $next($request);
    }
}
