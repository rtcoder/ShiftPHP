<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 08.07.18
 * Time: 23:54
 */

namespace Engine\Utils;


use \Engine\Error\ShiftError;
use \Engine\Error\StorageError;

class Storage {

    public $storageDir;
    public $storageViewsDir;

    public function __construct() {
        $this->storageDir = realpath(__DIR__ . '/../storage/');
        $this->storageViewsDir = $this->storageDir.'/views/';

        if (!file_exists($this->storageDir)) {
            throw new ShiftError('Storage directory ' . $this->storageDir . ' does not exists');
        }
        if (!is_writable($this->storageDir)) {
            throw new ShiftError('Storage directory ' . $this->storageDir . ' is not writable');
        }
    }

    public function saveView(string $name, string $content): void {
        if (!file_exists($this->storageViewsDir)) {
            mkdir($this->storageViewsDir, 0777, true);
        }

        file_put_contents($this->storageViewsDir.$name, $content);
    }
}