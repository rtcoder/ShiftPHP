<?php
/**
 * Created by PhpStorm.
 * User: dawidjez
 * Date: 14/12/2018
 * Time: 12:51
 */

namespace Shift\Console;


interface CommandInterface
{
    public function execute(mixed ...$args): void;

    public function getHelp(): string;

    public function getDescription(): string;
}
