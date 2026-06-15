<?php

namespace Engine;

use Engine\Error\ShiftError;
use Engine\Error\HttpError;
use ReflectionClass;
use ReflectionException;
use Throwable;

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
        try {
            $controller = $this->resolveController();
            $this->executeController($controller);
        } catch (HttpError $exception) {
            throw $exception;
        } catch (ReflectionException $exception) {
            throw new HttpError(
                'Endpoint not found',
                404,
                $exception
            );
        } catch (Throwable $exception) {
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
        $controllerName = ucfirst($this->request->getController() ?: $this->defaultController) . 'Controller';
        $controllerClass = 'Controllers\\' . $controllerName;

        if (!class_exists($controllerClass)) {
            throw new HttpError('Endpoint not found', 404);
        }

        return new $controllerClass($this->request, $this->container);
    }

    /**
     * @throws ReflectionException
     */
    private function executeController(object $controller): void
    {
        $reflectionClass = new ReflectionClass($controller);
        $methodName = $this->request->getAction() ?: $this->defaultAction;

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
