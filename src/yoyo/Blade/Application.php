<?php

namespace Clickfwd\Yoyo\Blade;

use Illuminate\Container\Container;

class Application extends Container
{
    public function getNamespace()
    {
        return '';
    }
    // public function runningInConsole()
    // {
    //     return false;
    // }

    // public function basePath($path)
    // {
    //     return $path;
    // }
}
