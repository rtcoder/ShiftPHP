<?php

namespace Engine;

/**
 * Class Controller
 * @package Engine
 */
abstract class Controller
{
    protected Request $request;
    protected View $view;

    public function __construct()
    {
        $this->request = new Request();
        $this->view = new View();
    }

    /**
     * @param string $view
     * @param array $data
     * @param string $title
     * @param array $styles
     * @param array $scripts
     * @return void
     */
    protected function render(string $view, array $data = [], string $title = '', array $styles = [], array $scripts = []): void
    {
        $this->view->make($view, $data, $title, $styles, $scripts);
    }

    /**
     * @param array $data
     * @return void
     */
    protected function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * @param string $url
     * @return never
     */
    protected function redirect(string $url): never
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * @return Request
     */
    protected function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return View
     */
    protected function getView(): View
    {
        return $this->view;
    }
}
