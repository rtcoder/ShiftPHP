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
     */
    public function render(string $view, array $data = []) {
        return (new View)->make($view, $data);
    }
}