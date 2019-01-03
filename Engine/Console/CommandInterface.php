<?php
/**
 * Created by PhpStorm.
 * User: dawidjez
 * Date: 14/12/2018
 * Time: 12:51
 */

namespace Engine\Console;


interface CommandInterface {
    public function execute(...$args): void;
}
