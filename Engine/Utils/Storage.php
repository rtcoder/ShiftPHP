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

    public string $storageDir;
    public string $storageViewsDir;

    public function __construct()
    {
        $storageParentDir = dirname(__DIR__) . '/';
        $this->storageDir = $storageParentDir . '/storage/';
        $this->storageViewsDir = $this->storageDir . '/views/';
        $this->checkDir($this->storageViewsDir);
    }

    private function checkDir(string $dir): void
    {
        if (!file_exists($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        if (!file_exists($dir)) {
            throw new StorageError('Directory ' . $dir . ' does not exists');
        }
        if (!is_writable($dir)) {
            throw new StorageError('Directory ' . $dir . ' is not writable');
        }
    }

    public function saveView(string $name, string $content): void
    {
        if (!file_exists($this->storageViewsDir) && !mkdir($concurrentDirectory = $this->storageViewsDir, 0777, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        file_put_contents($this->storageViewsDir . $name, $content);
    }
}
