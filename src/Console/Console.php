<?php
/**
 * Created by PhpStorm.
 * User: dawidjez
 * Date: 14/12/2018
 * Time: 12:09
 * Updated: 2025-06-22
 */

namespace Shift\Console;

/**
 * Główna klasa konsolowa
 */
class Console
{
    /**
     * Instancja klasy Cli do operacji terminalowych
     *
     * @var Cli
     */
    private Cli $cli;

    /**
     * Konstruktor klasy Console
     */
    public function __construct()
    {
        $this->cli = new Cli();
    }

    /**
     * Zwraca instancję klasy Cli
     *
     * @return Cli
     */
    public function cli(): Cli
    {
        return $this->cli;
    }

}
