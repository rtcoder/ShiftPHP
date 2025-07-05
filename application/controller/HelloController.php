<?php

namespace Controllers;

/**
 * Class HelloController
 * @package Controllers
 */
class HelloController extends \Engine\Controller
{
    public function index(): void
    {
        $this->render('default', [
            'message' => 'Hello from ShiftPHP!',
            'timestamp' => date('Y-m-d H:i:s')
        ], 'Welcome to ShiftPHP');
    }

    public function about(): void
    {
        $this->render('hello/about', [
            'title' => 'About ShiftPHP',
            'version' => '1.0.0'
        ], 'About - ShiftPHP');
    }

    public function api(): void
    {
        $this->json([
            'status' => 'success',
            'message' => 'API endpoint working!',
            'data' => [
                'controller' => $this->request->getController(),
                'action' => $this->request->getAction(),
                'arguments' => $this->request->getArguments()
            ]
        ]);
    }
}
