<?php

namespace Clickfwd\Yoyo\Containers;

use Clickfwd\Yoyo\Interfaces\YoyoContainerInterface;
use Closure;
use Illuminate\Container\Container;

class IlluminateContainer implements YoyoContainerInterface
{
    protected static $instance;

    protected $container;

    public static function getInstance()
    {
        return static::$instance = static::$instance ?? new static(Container::getInstance());
    }

    public function __construct(Container $container)
    {
        $this->container = $container;
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
        if (is_object($value) && ! ($value instanceof Closure)) {
            return $this->container->instance($id, $value);
        }

        $this->container->bind($id, $value, true);

        return $value;
    }

    public function make(string $class, array $args = [])
    {
        return $this->container->make($class, $args);
    }

    public function call($method, array $args = [])
    {
        return $this->container->call($method, $args);
    }
}
