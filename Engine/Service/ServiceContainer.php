<?php

namespace Shift\Service;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Class ServiceContainer
 * @package Shift
 */
class ServiceContainer
{
    private array $services = [];
    private array $singletons = [];
    private array $resolved = [];

    /**
     * Register a service
     */
    public function register(string $name, $service): void
    {
        $this->services[$name] = $service;
    }

    /**
     * Register a singleton service
     */
    public function singleton(string $name, $service): void
    {
        $this->singletons[$name] = $service;
    }

    /**
     * Resolve a service
     */
    public function resolve(string $name)
    {
        // Check if already resolved
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        // Check singletons first
        if (isset($this->singletons[$name])) {
            $service = $this->singletons[$name];
            $instance = $this->createInstance($service);
            $this->resolved[$name] = $instance;
            return $instance;
        }

        // Check regular services
        if (isset($this->services[$name])) {
            $service = $this->services[$name];
            return $this->createInstance($service);
        }

        throw new InvalidArgumentException("Service '{$name}' not found");
    }

    /**
     * Check if service exists
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]) || isset($this->singletons[$name]);
    }

    /**
     * Create instance from service definition
     */
    private function createInstance($service)
    {
        if ($service instanceof Closure) {
            return $service($this);
        }

        if (is_string($service) && class_exists($service)) {
            return $this->make($service);
        }

        return $service;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function make(string $class): object
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class '{$class}' not found");
        }

        try {
            $reflectionClass = new ReflectionClass($class);
        } catch (ReflectionException $exception) {
            throw new InvalidArgumentException("Class '{$class}' cannot be reflected", 0, $exception);
        }

        if (!$reflectionClass->isInstantiable()) {
            throw new InvalidArgumentException("Class '{$class}' is not instantiable");
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = array_map(
            fn (ReflectionParameter $parameter): mixed => $this->resolveParameter($parameter),
            $constructor->getParameters()
        );

        return $reflectionClass->newInstanceArgs($dependencies);
    }

    private function resolveParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $name = $type->getName();

            if ($this->has($name)) {
                return $this->resolve($name);
            }

            if (class_exists($name)) {
                return $this->make($name);
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new InvalidArgumentException("Unable to resolve parameter '{$parameter->getName()}'");
    }

    /**
     * Clear all services
     */
    public function clear(): void
    {
        $this->services = [];
        $this->singletons = [];
        $this->resolved = [];
    }

    /**
     * Get all registered service names
     */
    public function getRegisteredServices(): array
    {
        return array_keys($this->services);
    }

    /**
     * Get all singleton service names
     */
    public function getSingletonServices(): array
    {
        return array_keys($this->singletons);
    }
} 
