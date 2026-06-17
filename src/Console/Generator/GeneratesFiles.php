<?php

namespace Shift\Console\Generator;

use Shift\Console\Cli;

trait GeneratesFiles
{
    protected FileGenerator $files;

    protected StubRenderer $stubs;

    protected Cli $cli;

    public function __construct(
        protected readonly string $modulesPath = APP_PATH . '/modules'
    ) {
        $this->files = new FileGenerator();
        $this->stubs = new StubRenderer();
        $this->cli = new Cli();
    }

    /**
     * @param list<mixed> $args
     * @return array{0: string, 1: string}
     */
    protected function moduleAndClassFromArgs(array $args): array
    {
        $module = null;
        $name = null;

        foreach ($args as $arg) {
            if (!is_string($arg) || $arg === '') {
                continue;
            }

            if (str_starts_with($arg, '--module=')) {
                $module = substr($arg, 9);
                continue;
            }

            if ($module === null && str_contains($arg, ':')) {
                [$module, $name] = explode(':', $arg, 2);
                continue;
            }

            $name ??= $arg;
        }

        if ($module === null || $module === '' || $name === null || $name === '') {
            throw new \InvalidArgumentException('Module and class name are required.');
        }

        return [
            NameFormatter::moduleName($module),
            $name,
        ];
    }

    protected function modulePath(string $module): string
    {
        return rtrim($this->modulesPath, '/') . '/' . $module;
    }

    protected function writeAndReport(string $path, string $content): void
    {
        $beforeSkipped = count($this->files->skipped());
        $this->files->writeFile($path, $content);

        if (count($this->files->skipped()) > $beforeSkipped) {
            $this->cli->warning('Skipped existing file: ' . $path);
            return;
        }

        $this->cli->success('Created: ' . $path);
    }

    /**
     * @param array<string, string> $variables
     */
    protected function renderStub(string $stub, array $variables): string
    {
        return $this->stubs->render($stub, $variables);
    }
}
