<?php

namespace Clickfwd\Yoyo\Containers;

use \Closure;
use Illuminate\Container\Container;
use Clickfwd\Yoyo\Interfaces\YoyoContainerInterface;

class IlluminateContainer implements YoyoContainerInterface
{
    protected static $instance;

    public static function getInstance()
    {
        return static::$instance ??= new static(Container::getInstance());
    }

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getParent()
    {
        return $this->container;
    }

    public function get(string $id)
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    public function set(string $id, $value)
    {
        if(is_object($value) && !($value instanceof Closure)) {
            return $this->container->instance($id, $value);
        }

        $this->container->bind($id, $value, true);

        return $value;
    }

    public function make(string $class, array $args = [])
    {
        return $this->container->make($class, $args);
    }

    public function call(callable $method, array $args = [])
    {
        return $this->container->call($method, $args);
    }

    public function flush()
    {
        $this->container->flush();
    }
}
