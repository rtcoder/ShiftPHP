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
     * @return void
     */
    public function render(string $view, array $data = [], string $title = '') :void {
        (new View)->make($view, $data, $title);
    }
}
