<?php

namespace Engine;

use Engine\Error\ShiftError;
use ReflectionClass;
use ReflectionException;

/**
 * Class App
 * @package Engine
 */
final class App
{
    private Request $request;
    private ServiceContainer $container;
    private string $defaultController = 'index';
    private string $defaultAction = 'index';
    private bool $isRunning = false;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->container = new ServiceContainer();
        $this->registerDefaultServices();
    }

    /**
     * @throws ShiftError
     */
    public function start(): void
    {
        if ($this->isRunning) {
            return;
        }

        try {
            $controller = $this->resolveController();
            $this->executeController($controller);
            $this->isRunning = true;
        } catch (ReflectionException $exception) {
            throw new ShiftError(
                'Controller method not found: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception->getPrevious()
            );
        } catch (\Throwable $exception) {
            throw new ShiftError(
                'Application error: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception->getPrevious()
            );
        }
    }

    /**
     * @throws ShiftError
     */
    private function resolveController(): object
    {
        $controllerName = ucfirst($this->request->getController()) . 'Controller';
        $controllerClass = 'Controllers\\' . $controllerName;

        if (!class_exists($controllerClass)) {
            throw new ShiftError("Controller class '{$controllerClass}' not found");
        }

        return new $controllerClass();
    }

    /**
     * @throws ReflectionException
     */
    private function executeController(object $controller): void
    {
        $reflectionClass = new ReflectionClass($controller);
        $methodName = $this->request->getAction();

        if (!$reflectionClass->hasMethod($methodName)) {
            throw new ReflectionException("Method '{$methodName}' not found in controller");
        }

        $method = $reflectionClass->getMethod($methodName);
        $method->invokeArgs($controller, $this->request->getArguments());
    }

    /**
     * @param string $class_name
     */
    public static function autoload(string $class_name): void
    {
        $class = str_replace('_', '/',
            str_replace('\\', '/', $class_name)
        );

        if (empty($class)) {
            return;
        }

        $locations = [
            APP_PATH . '/controller/',
            APP_PATH . '/model/',
            APP_ROOT,
        ];

        foreach ($locations as $location) {
            if (file_exists($location . $class . '.php')) {
                require_once($location . $class . '.php');
            }
        }
    }

    public static function setHelpers(): void
    {
        require_once 'Utils/helpers.php';
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setDefaultController(string $controller): void
    {
        $this->defaultController = $controller;
    }

    public function setDefaultAction(string $action): void
    {
        $this->defaultAction = $action;
    }

    private function registerDefaultServices(): void
    {
        $this->container->singleton('request', $this->request);
        $this->container->singleton('view', function() {
            return new View();
        });
        $this->container->singleton('storage', function() {
            return new \Engine\Utils\Storage();
        });
    }

    public function getContainer(): ServiceContainer
    {
        return $this->container;
    }

    public function resolve(string $service)
    {
        return $this->container->resolve($service);
    }
}
