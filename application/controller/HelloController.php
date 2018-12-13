<?php

namespace Controllers;

/**
 * Class HelloController
 * @package Controllers
 */
class HelloController extends \Engine\Controller {

    public function index():void {
        $this->render('default', ['dd' => 'asd'], 'ssadfg');
    }
}
