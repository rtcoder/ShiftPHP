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
class View
{
    private string $title = '';
    private array $scripts = [];
    private array $styles = [];
    private Storage $storage;
    private string $templatePath;
    private string $viewPath;

    public function __construct()
    {
        $this->storage = new Storage();
        $this->templatePath = __DIR__ . '/../application/view/template/index.php';
        $this->viewPath = __DIR__ . '/../application/view/controller/';
    }

    /**
     * @param string|null $view
     * @param array $data
     * @param string $title
     * @param array $styles
     * @param array $scripts
     * @return void
     */
    public function make(?string $view, array $data = [], string $title = '', array $styles = [], array $scripts = []): void
    {
        $this->setTitle($title);
        $this->setScripts($scripts);
        $this->setStyles($styles);

        $viewName = $this->resolveViewName($view);
        $viewContent = $this->loadViewContent($viewName);
        $templateContent = $this->loadTemplateContent();
        
        $fullView = $this->buildFullView($templateContent, $viewContent);
        $this->renderView($fullView, $viewName, $data);
    }

    private function resolveViewName(?string $view): string
    {
        if (!$view || $view === 'default') {
            return Request::getController() . '/' . Request::getAction();
        }
        return $view;
    }

    /**
     * @throws ShiftError
     */
    private function loadViewContent(string $viewName): string
    {
        $viewFile = $this->viewPath . $viewName . '.php';
        
        if (!file_exists($viewFile)) {
            throw new ShiftError("View '{$viewName}' does not exist at path: {$viewFile}");
        }
        
        return file_get_contents($viewFile);
    }

    /**
     * @throws ShiftError
     */
    private function loadTemplateContent(): string
    {
        if (!file_exists($this->templatePath)) {
            throw new ShiftError("Template does not exist at path: {$this->templatePath}");
        }
        
        return file_get_contents($this->templatePath);
    }

    private function buildFullView(string $template, string $viewContent): string
    {
        return str_replace('{{ $view }}', $viewContent, $template);
    }

    private function renderView(string $fullView, string $viewName, array $data): void
    {
        $builder = new ViewBuilder($fullView);
        $builder
            ->setScripts($this->scripts)
            ->setStyles($this->styles)
            ->setTitle($this->title)
            ->build();

        $viewFileName = md5($viewName) . '.php';
        $this->storage->saveView($viewFileName, $builder->getView());

        // Extract variables for view
        extract($data);
        
        require_once $this->storage->getStorageViewsDir() . $viewFileName;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return array
     */
    public function getScripts(): array
    {
        return $this->scripts;
    }

    /**
     * @param array $scripts
     */
    public function setScripts(array $scripts): void
    {
        $this->scripts = $scripts;
    }

    /**
     * @return array
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * @param array $styles
     */
    public function setStyles(array $styles): void
    {
        $this->styles = $styles;
    }
}
