<?php

namespace Clickfwd\Yoyo;

use Closure;

class InvocableComponentVariable
{
    protected $callable;

    public function __construct(Closure $callable)
    {
        $this->callable = $callable;
    }

    public function __get($key)
    {
        return $this->__invoke()->{$key};
    }

    public function __call($method, $parameters)
    {
        return $this->__invoke()->{$method}(...$parameters);
    }

    public function __invoke()
    {
        return call_user_func($this->callable);
    }

    public function __toString()
    {
        return (string) $this->__invoke();
    }
}
