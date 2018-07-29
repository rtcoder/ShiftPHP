<?php

namespace Controllers;

class HelloController extends \Engine\Controller {

    public function index() {
        return $this->render('default', ['dd' => 'asd']);
    }
}