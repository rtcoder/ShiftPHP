<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 08.07.18
 * Time: 23:54
 */

namespace Engine\Utils;


use Engine\Error\StorageError;
use RuntimeException;

class Storage
{
    private string $storageDir;
    private string $storageViewsDir;

    public function __construct()
    {
        $this->initializePaths();
        $this->ensureDirectoriesExist();
    }

    private function initializePaths(): void
    {
        $storageParentDir = dirname(__DIR__) . '/';
        $this->storageDir = $storageParentDir . 'storage/';
        $this->storageViewsDir = $this->storageDir . 'views/';
    }

    private function ensureDirectoriesExist(): void
    {
        $this->createDirectoryIfNotExists($this->storageDir);
        $this->createDirectoryIfNotExists($this->storageViewsDir);
    }

    /**
     * @throws StorageError
     */
    private function createDirectoryIfNotExists(string $dir): void
    {
        if (file_exists($dir)) {
            $this->validateDirectory($dir);
            return;
        }

        if (!mkdir($dir, 0755, true)) {
            throw new StorageError("Failed to create directory: {$dir}");
        }

        $this->validateDirectory($dir);
    }

    /**
     * @throws StorageError
     */
    private function validateDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            throw new StorageError("Path '{$dir}' is not a directory");
        }

        if (!is_writable($dir)) {
            throw new StorageError("Directory '{$dir}' is not writable");
        }
    }

    /**
     * @throws StorageError
     */
    public function saveView(string $name, string $content): void
    {
        $filePath = $this->storageViewsDir . $name;
        
        if (file_put_contents($filePath, $content) === false) {
            throw new StorageError("Failed to save view file: {$filePath}");
        }
    }

    public function getStorageDir(): string
    {
        return $this->storageDir;
    }

    public function getStorageViewsDir(): string
    {
        return $this->storageViewsDir;
    }

    public function clearViews(): void
    {
        $files = glob($this->storageViewsDir . '*.php');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
