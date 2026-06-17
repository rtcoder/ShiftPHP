<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Modules\ModuleLoader;
use Shift\OpenApi\OpenApiGenerator;
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

        if ($outputPath === null) {
            echo $json . PHP_EOL;
            return;
        }

        $directory = dirname($outputPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($outputPath, $json . PHP_EOL);
        (new Cli())->success('OpenAPI document written to ' . $outputPath);
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift openapi [--output=docs/openapi.json]';
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
}
