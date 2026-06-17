<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Console\Quality\QualityChecks;
use Shift\Database\DatabaseConfig;
use Shift\Modules\ModuleLoader;
use Throwable;

#[\Shift\Console\Attributes\Command('doctor', aliases: ['check'], group: 'diagnostics')]
class Doctor implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $quality = new QualityChecks();
        $checks = [
            $this->phpVersion(),
            $this->extensions(),
            $this->composerConfig(),
            $quality->phpSyntax()->toRow(),
            $quality->testSuite()->toRow(),
            $this->environment(),
            $this->databaseConfig(),
            $this->moduleCache(),
        ];

        $failed = array_values(array_filter($checks, static fn (array $check): bool => $check[1] === 'fail'));
        $cli->table(['Check', 'Status', 'Details'], $checks);

        if ($failed === []) {
            $cli->success('Doctor checks passed.');
            return;
        }

        $cli->error('Doctor found ' . count($failed) . ' failing check(s).');
        exit(1);
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift doctor';
    }

    public function getDescription(): string
    {
        return 'Run project diagnostics.';
    }

    private function phpVersion(): array
    {
        return [
            'PHP version',
            version_compare(PHP_VERSION, '8.3.0', '>=') ? 'ok' : 'fail',
            PHP_VERSION,
        ];
    }

    private function extensions(): array
    {
        $missing = array_values(array_filter(
            ['json', 'pdo'],
            static fn (string $extension): bool => !extension_loaded($extension)
        ));

        return [
            'PHP extensions',
            $missing === [] ? 'ok' : 'fail',
            $missing === [] ? 'json, pdo' : 'Missing: ' . implode(', ', $missing),
        ];
    }

    private function composerConfig(): array
    {
        $path = APP_ROOT . '/composer.json';

        if (!is_file($path)) {
            return ['Composer config', 'fail', 'composer.json not found'];
        }

        json_decode((string) file_get_contents($path), true);

        return [
            'Composer config',
            json_last_error() === JSON_ERROR_NONE ? 'ok' : 'fail',
            json_last_error() === JSON_ERROR_NONE ? 'composer.json valid JSON' : json_last_error_msg(),
        ];
    }

    private function environment(): array
    {
        return [
            'Environment',
            is_file(APP_ROOT . '/.env') ? 'ok' : 'warn',
            is_file(APP_ROOT . '/.env') ? '.env present' : '.env not found',
        ];
    }

    private function databaseConfig(): array
    {
        try {
            $config = DatabaseConfig::fromEnv();
        } catch (Throwable $exception) {
            return ['Database config', 'fail', $exception->getMessage()];
        }

        return [
            'Database config',
            $config->driver !== '' && $config->database !== '' ? 'ok' : 'warn',
            $config->driver . ':' . ($config->database !== '' ? $config->database : '(empty)'),
        ];
    }

    private function moduleCache(): array
    {
        $loader = new ModuleLoader();

        return [
            'Module cache',
            'ok',
            $loader->isCached() ? 'cached' : 'empty',
        ];
    }

}
