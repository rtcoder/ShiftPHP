<?php

namespace Controllers;

class HelloController extends \Engine\Controller {

    /**
     *
     */
    public function index() {
        $this->render('default', ['dd' => 'asd'], 'ssadfg');
    }
}