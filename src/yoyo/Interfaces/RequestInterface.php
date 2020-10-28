<?php

namespace Clickfwd\Yoyo\Interfaces;

interface RequestInterface
{
    public function all();

    public function except($keys);

    public function input($key, $default = null);

    public function drop($key);

    public function method();

    public function fullUrl();

    public function isYoyoRequest();

    public function windUp();

    public function triggerId();
}
