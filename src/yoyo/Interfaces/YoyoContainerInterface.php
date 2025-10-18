<?php

namespace Clickfwd\Yoyo\Interfaces;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;

interface YoyoContainerInterface extends ContainerInterface
{
    /**
     * Binds value to the container for later resolution
     *
     * @param string $id
     * @param Closure|object|string $value
     * @return void
     */
    public function set(string $id, $value);

    /**
     * Makes the class with provided attributes
     *
     * @param string $class
     * @param array $args
     * @return mixed
     */
    public function make(string $class, array $args = []);

    /**
     * Calls the method with provided attributes
     *
     * @param callable $method
     * @param array $args
     * @return mixed
     */
    public function call(callable $method, array $args = []);
}