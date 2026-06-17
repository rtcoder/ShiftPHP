<?php

namespace Shift\Console\Quality;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ProjectFileFinder
{
    /**
     * @param list<string>|null $paths
     * @param list<string> $hygieneExtensions
     */
    public function __construct(
        private readonly ?array $paths = null,
        private readonly array $hygieneExtensions = ['php', 'md', 'json', 'html', 'yml', 'yaml']
    ) {
    }

    /**
     * @return list<string>
     */
    public function phpFiles(): array
    {
        $files = array_values(array_filter(
            $this->allFiles(),
            static fn (string $file): bool => pathinfo($file, PATHINFO_EXTENSION) === 'php' || basename($file) === 'shift'
        ));

        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    public function hygieneFiles(): array
    {
        $files = array_values(array_filter($this->allFiles(), function (string $file): bool {
            if (basename($file) === 'shift') {
                return true;
            }

            return in_array(pathinfo($file, PATHINFO_EXTENSION), $this->hygieneExtensions, true);
        }));

        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    private function allFiles(): array
    {
        $files = [];

        foreach ($this->paths ?? $this->defaultPaths() as $path) {
            if (is_file($path)) {
                $files[] = $path;
                continue;
            }

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $pathName = $file->getPathname();

                if ($this->isIgnored($pathName)) {
                    continue;
                }

                $files[] = $pathName;
            }
        }

        return array_values(array_unique($files));
    }

    /**
     * @return list<string>
     */
    private function defaultPaths(): array
    {
        return [
            APP_ROOT . '/src',
            APP_ROOT . '/application',
            APP_ROOT . '/database/migrations',
            APP_ROOT . '/tests',
            APP_ROOT . '/docs',
            APP_ROOT . '/.github',
            APP_ROOT . '/README.md',
            APP_ROOT . '/REFACTORING.md',
            APP_ROOT . '/composer.json',
            APP_ROOT . '/bootstrap.php',
            APP_ROOT . '/index.php',
            APP_ROOT . '/shift',
        ];
    }

    private function isIgnored(string $path): bool
    {
        foreach (['/.git/', '/vendor/', '/.idea/', '/storage/cache/', '/storage/logs/'] as $ignored) {
            if (str_contains($path, $ignored)) {
                return true;
            }
        }

        return false;
    }
}
