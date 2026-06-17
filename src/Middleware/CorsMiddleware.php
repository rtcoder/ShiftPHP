<?php

namespace Shift\Middleware;

use Shift\Request;
use Shift\Response\Response;

class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly array $allowedOrigins = ['*'],
        private readonly array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        private readonly array $allowedHeaders = ['Content-Type', 'Authorization'],
        private readonly int $maxAge = 600
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new Response('', 204, $this->headersFor($request));
        }

        $response = $next($request);

        return new Response(
            $response->getContent(),
            $response->getStatusCode(),
            $response->getHeaders() + $this->headersFor($request)
        );
    }

    private function headersFor(Request $request): array
    {
        $origin = $request->getHeader('Origin') ?? '*';
        $allowedOrigin = in_array('*', $this->allowedOrigins, true)
            ? '*'
            : (in_array($origin, $this->allowedOrigins, true) ? $origin : $this->allowedOrigins[0]);

        return [
            'Access-Control-Allow-Origin' => $allowedOrigin,
            'Access-Control-Allow-Methods' => implode(', ', $this->allowedMethods),
            'Access-Control-Allow-Headers' => implode(', ', $this->allowedHeaders),
            'Access-Control-Max-Age' => (string) $this->maxAge,
        ];
    }
}
