<?php
declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Enterprise IoC (Inversion of Control) / Dependency Injection Container
 * Automatically resolves dependencies using reflection and registers singletons/bindings.
 */
class Container
{
    private static ?Container $instance = null;
    private array $bindings = [];
    private array $instances = [];

    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Bind an abstract interface/key to a concrete class or closure
     */
    public function bind(string $abstract, mixed $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Register a shared singleton instance
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null; // Marked for singleton caching on first resolve
    }

    /**
     * Resolve and instantiate class with all constructor dependencies recursively
     */
    public function make(string $abstract): mixed
    {
        // Check if singleton already created
        if (array_key_exists($abstract, $this->instances) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;

        if ($concrete instanceof Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        // Cache if registered as singleton
        if (array_key_exists($abstract, $this->instances)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    private function build(string $concrete): object
    {
        if (!class_exists($concrete)) {
            throw new RuntimeException("Target class [{$concrete}] does not exist.");
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Target class [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // No constructor, instantiate immediately
        if ($constructor === null) {
            return new $concrete();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new RuntimeException("Unresolvable dependency [{$parameter->getName()}] in class [{$concrete}].");
                }
            } else {
                $dependencies[] = $this->make($type->getName());
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
