<?php

namespace Shift\Console\Quality;

final class QualityChecks
{
    public function __construct(
        private readonly ?ProjectFileFinder $files = null,
        private readonly ?string $root = null
    ) {
    }

    /**
     * @return list<CheckResult>
     */
    public function lint(): array
    {
        return [
            $this->phpSyntax(),
            $this->fileHygiene(),
        ];
    }

    /**
     * @return list<CheckResult>
     */
    public function qa(): array
    {
        return [
            $this->composerValidate(),
            $this->phpSyntax(),
            $this->fileHygiene(),
            $this->testSuite(),
            $this->routeList(),
            $this->openApiDocument(),
        ];
    }

    public function composerValidate(): CheckResult
    {
        return $this->runShellCheck('Composer config', 'composer validate --no-check-publish 2>&1', 'composer.json valid');
    }

    public function phpSyntax(): CheckResult
    {
        $files = $this->fileFinder()->phpFiles();

        foreach ($files as $file) {
            $output = [];
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $exitCode);

            if ($exitCode !== 0) {
                return CheckResult::fail('PHP syntax', $this->relativePath($file) . ': ' . trim(implode(' ', $output)));
            }
        }

        return CheckResult::ok('PHP syntax', count($files) . ' file(s) checked');
    }

    public function fileHygiene(): CheckResult
    {
        $files = $this->fileFinder()->hygieneFiles();

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            if ($contents === false || $contents === '') {
                continue;
            }

            if (!str_ends_with($contents, "\n")) {
                return CheckResult::fail('File hygiene', $this->relativePath($file) . ': missing final newline');
            }

            foreach (preg_split('/\r?\n/', $contents) ?: [] as $line => $text) {
                if (preg_match('/[ \t]+$/', $text)) {
                    return CheckResult::fail('File hygiene', $this->relativePath($file) . ':' . ($line + 1) . ': trailing whitespace');
                }
            }
        }

        return CheckResult::ok('File hygiene', count($files) . ' file(s) checked');
    }

    public function testSuite(): CheckResult
    {
        return $this->runShellCheck('Test suite', 'composer test 2>&1', 'composer test passed');
    }

    public function routeList(): CheckResult
    {
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($this->projectRoot() . '/shift') . ' route:list 2>&1';

        return $this->runShellCheck('Route list', $command, './shift route:list passed');
    }

    public function openApiDocument(): CheckResult
    {
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($this->projectRoot() . '/shift') . ' openapi 2>&1';
        $previousDirectory = getcwd();

        if ($previousDirectory !== false) {
            chdir($this->projectRoot());
        }

        try {
            exec($command, $output, $exitCode);
        } finally {
            if ($previousDirectory !== false) {
                chdir($previousDirectory);
            }
        }

        if ($exitCode !== 0) {
            $details = trim(implode(' ', array_slice($output, -3)));

            return CheckResult::fail('OpenAPI document', $details !== '' ? $details : 'Command failed with exit code ' . $exitCode);
        }

        $document = json_decode(implode("\n", $output), true);

        if (!is_array($document) || ($document['openapi'] ?? null) !== '3.0.3') {
            return CheckResult::fail('OpenAPI document', 'Generated document is not valid OpenAPI JSON');
        }

        return CheckResult::ok('OpenAPI document', './shift openapi passed');
    }

    private function runShellCheck(string $name, string $command, string $successDetails): CheckResult
    {
        $previousDirectory = getcwd();

        if ($previousDirectory !== false) {
            chdir($this->projectRoot());
        }

        try {
            exec($command, $output, $exitCode);
        } finally {
            if ($previousDirectory !== false) {
                chdir($previousDirectory);
            }
        }

        if ($exitCode === 0) {
            return CheckResult::ok($name, $successDetails);
        }

        $details = trim(implode(' ', array_slice($output, -3)));

        return CheckResult::fail($name, $details !== '' ? $details : 'Command failed with exit code ' . $exitCode);
    }

    private function fileFinder(): ProjectFileFinder
    {
        return $this->files ?? new ProjectFileFinder();
    }

    private function projectRoot(): string
    {
        return $this->root ?? APP_ROOT;
    }

    private function relativePath(string $path): string
    {
        $root = rtrim($this->projectRoot(), '/') . '/';

        if (str_starts_with($path, $root)) {
            return substr($path, strlen($root));
        }

        return $path;
    }
}
