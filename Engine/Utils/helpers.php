<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 04.06.18
 * Time: 23:45
 */

/**
 * @param $str
 * @return mixed
 */
function __(string $str): string {
    return $str;
}

/**
 * @param mixed ...$args
 */
function dd(...$args) {
    \Engine\Utils\Debug::dd(...$args);
}

/**
 * @param mixed ...$args
 */
function d(...$args) {
    \Engine\Utils\Debug::d(...$args);
}

/**
 * @param mixed $url
 * @return bool
 */
function is_url($url): bool {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * @param mixed $url
 * @return bool
 */
function is_email($url): bool {
    return filter_var($url, FILTER_VALIDATE_EMAIL) !== false;
}


/**
 * Determines if a command exists on the current environment
 *
 * @param string $command The command to check
 * @return bool True if the command has been found ; otherwise, false.
 */
function command_exists($command) {
    $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';

    $process = proc_open(
        "$whereIsCommand $command",
        array(
            0 => array("pipe", "r"), //STDIN
            1 => array("pipe", "w"), //STDOUT
            2 => array("pipe", "w"), //STDERR
        ),
        $pipes
    );
    if ($process !== false) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $stdout != '';
    }

    return false;
}