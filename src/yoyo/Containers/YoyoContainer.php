<?php

namespace Clickfwd\Yoyo\Containers;

use \Closure;
use ReflectionClass;
use Clickfwd\Yoyo\Interfaces\YoyoContainerInterface;

class YoyoContainer implements YoyoContainerInterface
{
    protected static $instance;

    protected $bindings = [];

    public static function getInstance()
    {
        return static::$instance ??= new static;
    }

    public function get(string $id)
    {
        $resolved = $this->bindings[$id] ?? null;

        if($resolved instanceof Closure) {
            $this->bindings[$id] = $resolved($this);
        }

        if(is_string($resolved) && class_exists($resolved)) {
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
        if(is_object($class)) return $class;
        if(!class_exists($class)) return null;

        return new $class(...$this->extractArgs($class, '__construct', $args));
    }

    public function call(callable $method, array $args = [])
    {
        return $method(...$this->extractArgs($method[0], $method[1], $args));
    }

    protected function extractArgs($class, $method, $arguments)
    {
        if(!method_exists($class, $method)) return [];

        $result = [];
        $reflector = new ReflectionClass($class);

        foreach ($reflector->getMethod($method)->getParameters() as $parameter) {
            // Variadic arguments
            if($parameter->isVariadic()) {
                return array_merge($result, array_values($arguments));
            }
            // Named argument
            elseif(isset($arguments[$parameter->getName()])) {
                $result[] = $arguments[$parameter->getName()];
                unset($arguments[$parameter->getName()]);
            }
            // Typed argument
            elseif($parameter->hasType() && class_exists($parameter->getType()->getName())) {
                $result[] = $this->make($parameter->getType()->getName());
            }
            // Argument with default value
            elseif($parameter->isDefaultValueAvailable()) {
                $result[] = $parameter->getDefaultValue();
            }
            // Nullable argument
            elseif($parameter->allowsNull()) {
                $result[] = null;
            }
            // Default - assume explicit arguments
            else {
                return $arguments;
            }
        }

        return $result;
    }

    public function flush()
    {
        $this->bindings = [];
    }
}
