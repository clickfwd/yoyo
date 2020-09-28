<?php

namespace Clickfwd\Yoyo;

class View
{
    private $viewPath;

    private $cache = [];

    public function __construct($paths)
    {
        $paths = is_array($paths) ? $paths : [$paths];

        $this->viewPath = $paths;
    }

    /**
     * Forward method calls to their property closure function equivalent
     * Used for eventManager emit methods dynamically added to the view class.
     */
    public function __call($name, $args)
    {
        return call_user_func_array($this->$name, $args);
    }

    public function render($template, $vars = []): string
    {
        foreach ($vars as $key => $value) {
            $$key = $value;
        }

        ob_start();

        include $this->cache[$template];

        return ob_get_clean();
    }

    public function makeFromString($content, $vars = []): string
    {
        throw new \Exception('Views from strings are not supported with the native Yoyo view provider.');
    }

    public function exists($template)
    {
        foreach ($this->viewPath as $path) {
            if (file_exists("{$path}/{$template}.php")) {
                $this->cache[$template] = "{$path}/{$template}.php";

                return true;
            }
        }

        $this->cache[$template] = false;

        return false;
    }
}
