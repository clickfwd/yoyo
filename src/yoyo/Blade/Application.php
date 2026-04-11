<?php

namespace Clickfwd\Yoyo\Blade;

use Closure;
use Illuminate\Container\Container;

class Application extends Container
{
    protected array $terminatingCallbacks = [];

    public function getNamespace()
    {
        return '';
    }

    public function terminating(Closure $callback)
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    public function terminate()
    {
        foreach ($this->terminatingCallbacks as $terminatingCallback) {
            $terminatingCallback();
        }
    }
}
