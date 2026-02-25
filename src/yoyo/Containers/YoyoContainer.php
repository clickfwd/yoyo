<?php

namespace Clickfwd\Yoyo\Containers;

use Clickfwd\Yoyo\Exceptions\BindingNotFoundException;
use Clickfwd\Yoyo\Exceptions\ContainerResolutionException;
use Clickfwd\Yoyo\Interfaces\YoyoContainerInterface;

class YoyoContainer implements YoyoContainerInterface
{
    protected static $instance;

    protected $bindings = [];

    public static function getInstance()
    {
        return static::$instance = static::$instance ?? new static();
    }

    public function get(string $id)
    {
        if (! $this->has($id)) {
            throw new BindingNotFoundException("[$id] is not bound to the container");
        }

        $resolved = $this->bindings[$id];

        if ($resolved instanceof \Closure) {
            $this->bindings[$id] = $resolved($this);
        }

        if (is_string($resolved) && class_exists($resolved)) {
            return $this->make($resolved);
        }

        return $this->bindings[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }

    public function set(string $id, $value)
    {
        $this->bindings[$id] = $value;

        return $value;
    }

    public function make(string $class, array $args = [])
    {
        try {
            $class = $this->has($class) ? $this->get($class) : $class;
            return is_object($class) ? $class : new $class(...$this->extractArgs($class, '__construct', $args));
        } catch (\Throwable $e) {
            throw new ContainerResolutionException("[$class] could not be resolved", $e);
        }
    }

    public function call(callable $method, array $args = [])
    {
        if (! is_array($method) || count($method) !== 2) {
            throw new \InvalidArgumentException("Callable must be in [class, method] format");
        }

        return $method(...$this->extractArgs($method[0], $method[1], $args));
    }

    protected function extractArgs($class, $method, $arguments)
    {
        try {
            $result = [];
            $reflector = new \ReflectionClass($class);
            $parameters = $reflector->getMethod($method)->getParameters();
        } catch (\ReflectionException $e) {
            return $arguments;
        }

        foreach ($parameters as $parameter) {
            // Variadic arguments
            if ($parameter->isVariadic()) {
                return array_merge($result, array_values($arguments));
            }
            // Named argument
            elseif (isset($arguments[$parameter->getName()])) {
                $result[] = $arguments[$parameter->getName()];
                unset($arguments[$parameter->getName()]);
            }
            // Typed argument
            elseif ($this->isResolvableType($parameter)) {
                $result[] = $this->make($parameter->getType()->getName());
            }
            // Argument with default value
            elseif ($parameter->isDefaultValueAvailable()) {
                $result[] = $parameter->getDefaultValue();
            }
            // Nullable argument
            elseif ($parameter->allowsNull()) {
                $result[] = null;
            }
            // Default - assume explicit arguments
            else {
                return $arguments;
            }
        }

        return $result;
    }

    protected function isResolvableType(\ReflectionParameter $parameter): bool
    {
        if ($parameter->getType() instanceof \ReflectionNamedType) {
            $name = $parameter->getType()->getName();
            return $name && (class_exists($name) || interface_exists($name));
        }

        return false;
    }
}
