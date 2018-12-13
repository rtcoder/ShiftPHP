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
