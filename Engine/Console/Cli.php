<?php
/**
 * Klasa do obsługi wyjścia na terminal
 */

namespace Engine\Console;

class Cli
{
    // Kody kolorów ANSI
    private static string $COLOR_DEFAULT = "\033[0m";
    private static string $COLOR_BLUE = "\033[0;34m";
    private static string $COLOR_RED = "\033[0;31m";
    private static string $COLOR_GREEN = "\033[0;32m";
    private static string $COLOR_YELLOW = "\033[0;33m";

    /**
     * Wyświetla informację w kolorze niebieskim
     *
     * @param string $message Treść wiadomości
     * @param bool $newLine Czy dodać nową linię na końcu
     * @return void
     */
    public function info(string $message, bool $newLine = true): void
    {
        $this->output(self::$COLOR_BLUE . $message . self::$COLOR_DEFAULT, $newLine);
    }

    /**
     * Wyświetla informację debugowania w standardowym kolorze
     *
     * @param string $message Treść wiadomości
     * @param bool $newLine Czy dodać nową linię na końcu
     * @return void
     */
    public function debug(string $message, bool $newLine = true): void
    {
        $this->output($message, $newLine);
    }

    /**
     * Wyświetla informację o błędzie w kolorze czerwonym
     *
     * @param string $message Treść wiadomości
     * @param bool $newLine Czy dodać nową linię na końcu
     * @return void
     */
    public function error(string $message, bool $newLine = true): void
    {
        $this->output(self::$COLOR_RED . $message . self::$COLOR_DEFAULT, $newLine);
    }

    /**
     * Wyświetla informację o sukcesie w kolorze zielonym
     *
     * @param string $message Treść wiadomości
     * @param bool $newLine Czy dodać nową linię na końcu
     * @return void
     */
    public function success(string $message, bool $newLine = true): void
    {
        $this->output(self::$COLOR_GREEN . $message . self::$COLOR_DEFAULT, $newLine);
    }

    /**
     * Wyświetla ostrzeżenie w kolorze żółtym
     *
     * @param string $message Treść wiadomości
     * @param bool $newLine Czy dodać nową linię na końcu
     * @return void
     */
    public function warning(string $message, bool $newLine = true): void
    {
        $this->output(self::$COLOR_YELLOW . $message . self::$COLOR_DEFAULT, $newLine);
    }

    /**
     * Wyświetla linię separatora
     *
     * @param int $length Długość linii
     * @param string $char Znak używany do narysowania linii
     * @return void
     */
    public function line(int $length = 50, string $char = '-'): void
    {
        $this->output(str_repeat($char, $length));
    }

    /**
     * Wyświetla tekst z tabulacją
     *
     * @param string $message Treść wiadomości
     * @param int $level Poziom wcięcia
     * @param bool $newLine Czy dodać nową linię na końcu
     * @return void
     */
    public function indent(string $message, int $level = 1, bool $newLine = true): void
    {
        $indent = str_repeat("  ", $level); // dwa spacje na poziom
        $this->output($indent . $message, $newLine);
    }

    /**
     * Wyświetla tabelę danych
     *
     * @param array $headers Nagłówki tabeli
     * @param array $rows Wiersze z danymi
     * @return void
     */
    public function table(array $headers, array $rows): void
    {
        // Obliczanie szerokości kolumn
        $columnWidths = [];
        foreach ($headers as $i => $header) {
            $columnWidths[$i] = strlen($header);
            foreach ($rows as $row) {
                if (isset($row[$i]) && strlen($row[$i]) > $columnWidths[$i]) {
                    $columnWidths[$i] = strlen($row[$i]);
                }
            }
        }

        // Rysowanie górnej granicy
        $this->drawTableBorder($columnWidths);

        // Rysowanie nagłówków
        $this->drawTableRow($headers, $columnWidths);

        // Rysowanie linii rozdzielającej
        $this->drawTableBorder($columnWidths);

        // Rysowanie wierszy z danymi
        foreach ($rows as $row) {
            $this->drawTableRow($row, $columnWidths);
        }

        // Rysowanie dolnej granicy
        $this->drawTableBorder($columnWidths);
    }

    /**
     * Wyświetla postęp operacji
     *
     * @param int $current Aktualna wartość
     * @param int $total Całkowita wartość
     * @param int $barWidth Szerokość paska postępu
     * @return void
     */
    public function progressBar(int $current, int $total, int $barWidth = 50): void
    {
        $percent = $current / $total;
        $bar = floor($percent * $barWidth);
        $status = str_pad("[", 1);
        $status .= str_repeat("=", $bar);
        if ($bar < $barWidth) {
            $status .= ">";
            $status .= str_repeat(" ", $barWidth - $bar - 1);
        } else {
            $status .= "=";
        }
        $status .= "]";
        $status .= " " . number_format($percent * 100, 0) . "%";
        $status .= " $current/$total";

        echo "\r"; // Powrót karetki
        $this->output($status, false);

        if ($current === $total) {
            echo "\n"; // Nowa linia na końcu postępu
        }

        // Sprawdzamy czy bufor jest aktywny przed próbą jego opróżnienia
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    /**
     * Pobiera wejście od użytkownika
     *
     * @param string $prompt Zapytanie wyświetlane użytkownikowi
     * @param string|null $default Domyślna wartość
     * @return string Wprowadzona wartość
     */
    public function input(string $prompt, ?string $default = null): string
    {
        $promptText = $prompt;
        if ($default !== null) {
            $promptText .= " [" . $default . "]";
        }
        $promptText .= ": ";

        $this->output($promptText, false);
        $input = trim(fgets(STDIN));

        if ($input === '' && $default !== null) {
            return $default;
        }

        return $input;
    }

    /**
     * Pobiera potwierdzenie od użytkownika
     *
     * @param string $prompt Zapytanie wyświetlane użytkownikowi
     * @param bool $default Domyślna wartość
     * @return bool Wynik potwierdzenia
     */
    public function confirm(string $prompt, bool $default = true): bool
    {
        $defaultText = $default ? 'T' : 'N';
        $options = $default ? '[T/n]' : '[t/N]';

        $input = $this->input("$prompt $options", $defaultText);
        return in_array(strtolower($input), ['t', 'tak', 'y', 'yes']);
    }

    /**
     * Wyświetla wiadomość
     *
     * @param string $message Treść wiadomości
     * @param bool $newLine Czy dodać nową linię
     * @return void
     */
    private function output(string $message, bool $newLine = true): void
    {
        echo $message . ($newLine ? PHP_EOL : '');
    }

    /**
     * Rysuje wiersz tabeli
     *
     * @param array $data Dane wiersza
     * @param array $columnWidths Szerokości kolumn
     * @return void
     */
    private function drawTableRow(array $data, array $columnWidths): void
    {
        echo "|";
        foreach ($columnWidths as $i => $width) {
            $value = $data[$i] ?? '';
            echo " " . str_pad($value, $width) . " |";
        }
        echo PHP_EOL;
    }

    /**
     * Rysuje granicę tabeli
     *
     * @param array $columnWidths Szerokości kolumn
     * @return void
     */
    private function drawTableBorder(array $columnWidths): void
    {
        echo "+";
        foreach ($columnWidths as $width) {
            echo "-" . str_repeat("-", $width) . "-+";
        }
        echo PHP_EOL;
    }
}
