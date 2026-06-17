<?php

namespace Shift\Console\Generator;

final class FileGenerator
{
    /** @var list<string> */
    private array $created = [];

    /** @var list<string> */
    private array $skipped = [];

    public function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        mkdir($path, 0775, true);
        $this->created[] = $path;
    }

    public function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);
        $this->ensureDirectory($directory);

        if (file_exists($path)) {
            $this->skipped[] = $path;
            return;
        }

        file_put_contents($path, $content);
        $this->created[] = $path;
    }

    /**
     * @return list<string>
     */
    public function created(): array
    {
        return $this->created;
    }

    /**
     * @return list<string>
     */
    public function skipped(): array
    {
        return $this->skipped;
    }
}
