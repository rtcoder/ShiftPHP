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

    private $_title = '';

    /**
     * @param null|string $view
     * @param array $data
     * @param string $title
     */
    public function make(?string $view, array $data = [], string $title = '') {

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

        $builder = new ViewBuilder($fullView);
        $builder->setScripts()->setStyles();
        if (strlen($title)) {
            $builder->setTitle($title);
        }
        $builder->build();

        $storage->saveView(
            $viewName,
            $builder->getView()
        );

        require_once $storage->storageViewsDir . $viewName;

    }

    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->_title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void {
        $this->_title = $title;
    }
}
