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
     * @return
     */
    public function render(string $view, array $data = [], $title = '') {
        (new View)->make($view, $data, $title);
    }
}