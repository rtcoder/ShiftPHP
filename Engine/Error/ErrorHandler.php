<?php

namespace Engine\Error;

use Throwable;

/**
 * Class ErrorHandler
 * @package Engine\Error
 */
class ErrorHandler
{
    private static bool $isRegistered = false;
    /** @var callable|null */
    private static mixed $customHandler = null;

    public static function register(): void
    {
        if (self::$isRegistered) {
            return;
        }

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$isRegistered = true;
    }

    public static function setCustomHandler(callable $handler): void
    {
        self::$customHandler = $handler;
    }

    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (error_reporting() & $level) {
            $exception = new ShiftError($message, $level);
            $exception->setFile($file);
            $exception->setLine($line);
            
            self::handleException($exception);
            return true;
        }

        return false;
    }

    public static function handleException(Throwable $exception): void
    {
        if (self::$customHandler) {
            call_user_func(self::$customHandler, $exception);
            return;
        }

        if (php_sapi_name() === 'cli') {
            self::renderCliError($exception);
        } else {
            self::renderWebError($exception);
        }
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $exception = new ShiftError($error['message'], $error['type']);
            $exception->setFile($error['file']);
            $exception->setLine($error['line']);
            
            self::handleException($exception);
        }
    }

    private static function renderCliError(Throwable $exception): void
    {
        echo "\n";
        echo "Fatal Error: " . $exception->getMessage() . "\n";
        echo "File: " . $exception->getFile() . "\n";
        echo "Line: " . $exception->getLine() . "\n";
        echo "\nStack Trace:\n";
        echo $exception->getTraceAsString() . "\n";
        echo "\n";
    }

    private static function renderWebError(Throwable $exception): void
    {
        $statusCode = $exception instanceof HttpError ? $exception->getStatusCode() : 500;
        http_response_code($statusCode);

        header('Content-Type: application/json');

        $payload = [
            'error' => [
                'message' => 'Internal Server Error',
                'status' => $statusCode,
            ],
        ];

        $displayDetails = self::shouldDisplayDetails();

        if ($exception instanceof HttpError || $displayDetails) {
            $payload['error']['message'] = $exception->getMessage();
        }

        if ($displayDetails) {
            $payload['error']['file'] = $exception->getFile();
            $payload['error']['line'] = $exception->getLine();
        }

        echo json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private static function shouldDisplayDetails(): bool
    {
        return in_array(strtolower((string) ini_get('display_errors')), ['1', 'on', 'true'], true);
    }
} 
