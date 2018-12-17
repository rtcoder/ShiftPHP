<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 08.07.18
 * Time: 23:54
 */

namespace Engine\Utils;


use Engine\Error\StorageError;

class Storage {

    public $storageDir;
    public $storageViewsDir;

    public function __construct() {
        $storageParentDir = realpath(__DIR__ . '/../');
        $this->storageDir = $storageParentDir . '/storage/';
        $this->storageViewsDir = $this->storageDir . '/views/';
        $this->checkDir($this->storageViewsDir);
    }

    public function saveView(string $name, string $content): void {
        if (!file_exists($this->storageViewsDir)) {
            mkdir($this->storageViewsDir, 0777, true);
        }

        file_put_contents($this->storageViewsDir . $name, $content);
    }

    private function checkDir(string $dir): void {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!file_exists($dir)) {
            throw new StorageError('Directory ' . $dir . ' does not exists');
        }
        if (!is_writable($dir)) {
            throw new StorageError('Directory ' . $dir . ' is not writable');
        }
    }
}
