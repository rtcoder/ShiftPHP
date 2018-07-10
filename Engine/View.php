<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 04.06.18
 * Time: 23:48
 */

namespace Engine;

use Engine\Error\ShiftError;
use Engine\Utils\Storage;


/**
 * Class View
 * @package Engine
 */
class View {
    /**
     * @param $view
     * @param array $data
     */
    public function make(?string $view, array $data = []) {

        if (!$view || strlen($view) === 0) {
            $view = 'default';
        }

        $storage = new Storage();

        if (!file_exists(__DIR__ . '/../application/view/template/index.php')) {
            throw new ShiftError('Template ' . $view . ' does not exists');
        }
        $template = file_get_contents(__DIR__ . '/../application/view/template/index.php');

        if ($view === 'default') {
            $view = Request::getController() . '/' . Request::getAction();
        }

        if (!file_exists(__DIR__ . '/../application/view/' . $view . '.php')) {
            throw new ShiftError('View ' . $view . ' does not exists');
        }
        $viewContent = file_get_contents(__DIR__ . '/../application/view/' . $view . '.php');

        $viewName = md5($view) . '.php';

        $fullView = str_replace('{{ $view }}', $viewContent, $template);

        $storage->saveView(
            $viewName,
            str_replace('{{ $view }}', $viewContent, $template)
        );

        preg_replace_callback(
            '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', function ($match) {
            return $this->compileStatement($match);
        }, $fullView
        );

        die();
        require_once $storage->storageViewsDir . $viewName;

    }

    private function compileStatement($match) {
        echo '<pre>';
        var_dump($match);
//        die;
    }
}