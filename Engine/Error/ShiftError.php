<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 07.07.18
 * Time: 17:58
 */

namespace Shift\Error;


use Throwable;

/**
 * Class ShiftError
 * @package Shift\Error
 */
class ShiftError extends \Error
{
    private string $customFile = '';
    private int $customLine = 0;

    /**
     * ShiftError constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @return void
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function setFile(string $file): void
    {
        $this->customFile = $file;
    }

    public function setLine(int $line): void
    {
        $this->customLine = $line;
    }

    public function getCustomFile(): string
    {
        return $this->customFile ?: $this->getFile();
    }

    public function getCustomLine(): int
    {
        return $this->customLine ?: $this->getLine();
    }
}
