<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Containers\IlluminateContainer;
use Clickfwd\Yoyo\Containers\YoyoContainer;
use Clickfwd\Yoyo\Interfaces\YoyoContainerInterface;
use Illuminate\Container\Container;

class ContainerResolver
{
    protected static $preferred = null;

    public static function setPreferred(?YoyoContainerInterface $container)
    {
        static::$preferred = $container;
    }

    public static function getPreferred()
    {
        return static::$preferred;
    }

    public static function resolve(): YoyoContainerInterface
    {
        if(static::$preferred) {
            return static::$preferred;
        }

        if(class_exists(Container::class)) {
            return new IlluminateContainer(Container::getInstance());
        }

        return YoyoContainer::getInstance();
    }
}
