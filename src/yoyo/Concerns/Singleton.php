<?php

namespace Clickfwd\Yoyo\Concerns;

trait Singleton
{
    /**
     * @var reference to singleton instance
     */
    private static $instance;

    /**
     * Creates a new instance of a singleton class (via late static binding),
     * accepting a variable-length argument list.
     *
     * @return self
     */
    final public static function getInstance(...$params)
    {
        if (! isset(static::$instance)) {
            static::$instance = new static(...$params);
        }

        return static::$instance;
    }

    /**
     * Prevents cloning the singleton instance.
     *
     * @return void
     */
    public function __clone()
    {
    }

    /**
     * Prevents unserializing the singleton instance.
     *
     * @return void
     */
    public function __wakeup()
    {
    }
}
