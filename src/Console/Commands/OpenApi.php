<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Modules\ModuleLoader;
use Shift\OpenApi\OpenApiGenerator;
use Shift\OpenApi\OpenApiLivePage;
use Shift\OpenApi\OpenApiValidator;
use Shift\Routing\Router\Router;

#[\Shift\Console\Attributes\Command('openapi', aliases: ['api:docs'], group: 'documentation')]
class OpenApi implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $router = new Router();
        (new ModuleLoader())->load()->registerRoutes($router);

        $document = (new OpenApiGenerator())->generate($router);
        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            (new Cli())->error('Unable to encode OpenAPI document.');
            exit(1);
        }

        $outputPath = $this->outputPath($args);
        $live = $this->hasOption($args, '--live');
        $validate = $this->hasOption($args, '--validate');

        if ($validate) {
            $errors = (new OpenApiValidator())->validate($document);

            if ($errors !== []) {
                $cli = new Cli();

                foreach ($errors as $error) {
                    $cli->error($error);
                }

                exit(1);
            }
        }

        if ($outputPath === null && !$live && !$validate) {
            echo $json . PHP_EOL;
            return;
        }

        if ($validate) {
            (new Cli())->success('OpenAPI document is valid.');
        }

        if ($outputPath !== null) {
            $this->writeFile($outputPath, $json . PHP_EOL);
            (new Cli())->success('OpenAPI document written to ' . $outputPath);
        }

        if ($live) {
            $this->serveLiveDocumentation($json, $args);
        }
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift openapi [--output=docs/openapi.json] [--validate] [--live] [--host=127.0.0.1] [--port=8088]';
    }

    public function getDescription(): string
    {
        return 'Generate an OpenAPI JSON document from registered routes.';
    }

    private function outputPath(array $args): ?string
    {
        foreach ($args as $arg) {
            if (!is_string($arg) || !str_starts_with($arg, '--output=')) {
                continue;
            }

            $path = trim(substr($arg, 9));

            if ($path === '') {
                return null;
            }

            return str_starts_with($path, '/') ? $path : APP_ROOT . '/' . $path;
        }

        return null;
    }

    private function serveLiveDocumentation(string $json, array $args): void
    {
        $host = $this->optionValue($args, '--host=') ?? '127.0.0.1';
        $port = $this->optionValue($args, '--port=') ?? '8088';
        $root = sys_get_temp_dir() . '/shift-openapi-live-' . bin2hex(random_bytes(6));

        mkdir($root, 0775, true);
        $this->writeFile($root . '/openapi.json', $json . PHP_EOL);
        $this->writeFile($root . '/index.html', (new OpenApiLivePage())->render());

        $url = 'http://' . $host . ':' . $port;
        $cli = new Cli();
        $cli->info('OpenAPI live documentation: ' . $url);
        $cli->debug('Press Ctrl+C to stop the server.');

        passthru(escapeshellarg(PHP_BINARY) . ' -S ' . escapeshellarg($host . ':' . $port) . ' -t ' . escapeshellarg($root), $exitCode);

        if ($exitCode !== 0) {
            $cli->error('OpenAPI live server stopped with exit code ' . $exitCode . '.');
            exit($exitCode);
        }
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, $contents);
    }

    private function hasOption(array $args, string $option): bool
    {
        foreach ($args as $arg) {
            if ($arg === $option) {
                return true;
            }
        }

        return false;
    }

    private function optionValue(array $args, string $prefix): ?string
    {
        foreach ($args as $arg) {
            if (!is_string($arg) || !str_starts_with($arg, $prefix)) {
                continue;
            }

            $value = trim(substr($arg, strlen($prefix)));

            return $value !== '' ? $value : null;
        }

        return null;
    }
}
