<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Database\DatabaseConfig;
use Shift\Modules\ModuleLoader;
use Throwable;

#[\Shift\Console\Attributes\Command('doctor', aliases: ['check'], group: 'diagnostics')]
class Doctor implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $checks = [
            $this->phpVersion(),
            $this->extensions(),
            $this->composerConfig(),
            $this->phpLint(),
            $this->testSuite(),
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

    private function phpLint(): array
    {
        $files = array_merge(
            $this->phpFiles(APP_ROOT . '/src'),
            $this->phpFiles(APP_ROOT . '/application'),
            $this->phpFiles(APP_ROOT . '/tests'),
            [APP_ROOT . '/shift']
        );

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $exitCode);

            if ($exitCode !== 0) {
                return ['PHP lint', 'fail', basename($file) . ': ' . trim(implode(' ', $output))];
            }
        }

        return ['PHP lint', 'ok', count($files) . ' file(s) checked'];
    }

    private function testSuite(): array
    {
        exec('composer test 2>&1', $output, $exitCode);

        return [
            'Test suite',
            $exitCode === 0 ? 'ok' : 'fail',
            $exitCode === 0 ? 'composer test passed' : trim(implode(' ', array_slice($output, -3))),
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

    private function phpFiles(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
