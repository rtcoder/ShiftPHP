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
     * @var string
     */
    private $_title = '';
    private $_scripts = [];
    private $_styles = [];


    /**
     * @param null|string $view
     * @param array $data
     * @param string $title
     * @param array $styles
     * @param array $scripts
     */
    public function make(?string $view, array $data = [], string $title = '', array $styles = [], array $scripts = []) {
        $this->setTitle($title);
        $this->setScripts($scripts);
        $this->setStyles($styles);

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

        $builder
            ->setScripts($this->_scripts)
            ->setStyles($this->_styles)
            ->setTitle($this->_title)
            ->build();

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

    /**
     * @return array
     */
    public function getScripts(): array {
        return $this->_scripts;
    }

    /**
     * @param array $scripts
     */
    public function setScripts(array $scripts): void {
        $this->_scripts = $scripts;
    }

    /**
     * @return array
     */
    public function getStyles(): array {
        return $this->_styles;
    }

    /**
     * @param array $styles
     */
    public function setStyles(array $styles): void {
        $this->_styles = $styles;
    }
}
