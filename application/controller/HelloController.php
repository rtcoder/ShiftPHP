<?php

namespace Controllers;

use Tools\Config;

class HelloController extends \Engine\Controller {

    public function index() {
        return $this->render('default', ['dd' => 'asd']);
    }
}