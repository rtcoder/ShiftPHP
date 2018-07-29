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
use View\ViewBuilder;


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

        if (!file_exists(__DIR__ . '/../application/view/controller/' . $view . '.php')) {
            throw new ShiftError('View ' . $view . ' does not exists');
        }
        $viewContent = file_get_contents(__DIR__ . '/../application/view/controller/' . $view . '.php');

        $viewName = md5($view) . '.php';

        $fullView = str_replace('{{ $view }}', $viewContent, $template);

        $builder = (new ViewBuilder($fullView))
            ->setScripts()
            ->setStyles()
            ->build();

        $storage->saveView(
            $viewName,
            $builder->getView()
        );

        require_once $storage->storageViewsDir . $viewName;

    }
}