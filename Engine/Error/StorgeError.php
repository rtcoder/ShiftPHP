<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 07.07.18
 * Time: 17:58
 */

namespace Engine\Error;


use Throwable;

/**
 * Class StorageError
 * @package Engine\Error
 */
class StorageError extends ShiftError {

    /**
     * StorageError constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}