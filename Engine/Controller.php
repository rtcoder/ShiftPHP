<?php

namespace Engine;

/**
 * Class Controller
 * @package Engine
 */
class Controller {

    /**
     * @param string $view
     * @param array $data
     * @param string $title
     * @param array $styles
     * @param array $scripts
     * @return void
     */
    public function render(string $view, array $data = [], string $title = '', array $styles = [], array $scripts = []): void {
        (new View)->make($view, $data, $title, $styles, $scripts);
    }
}
