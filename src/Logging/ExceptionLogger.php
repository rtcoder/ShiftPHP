<?php

namespace Shift\Logging;

use Shift\Error\HttpError;
use Shift\Request;
use Throwable;

final class ExceptionLogger
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public static function default(): self
    {
        return new self(LoggerFactory::fromEnv());
    }

    public function log(Throwable $exception, ?Request $request = null, ?int $statusCode = null): void
    {
        $statusCode ??= $exception instanceof HttpError ? $exception->getStatusCode() : 500;
        $level = $statusCode >= 500 ? LogLevel::ERROR : LogLevel::WARNING;

        $context = [
            'exception' => $exception::class,
            'status' => $statusCode,
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if ($request instanceof Request) {
            $context['request'] = [
                'method' => $request->getMethod(),
                'path' => $request->getPath(),
                'ip' => $request->getIpAddress(),
                'user_agent' => $request->getUserAgent(),
                'request_id' => $request->getHeader('X-Request-Id'),
            ];
        }

        $this->logger->log($level, $exception->getMessage(), $context);
    }
}
