<?php

namespace Shift\Middleware;

use Shift\Request;
use Shift\Response\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
