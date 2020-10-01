<?php

namespace Clickfwd\Yoyo;

class View
{
    private $viewPath;

    private $templatePathsCache = [];

    private $yoyoComponent;

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

    public function startYoyoRendering($component)
    {
        $this->yoyoComponent = $component;

        return $this;
    }

    public function render($template, $vars = []): string
    {
        ob_start();

        $path = $this->templatePathsCache[$template];

        \Closure::bind(function () use ($path, $vars) {
            extract($vars, EXTR_SKIP);
            include $path;
        }, $this->yoyoComponent ? $this->yoyoComponent : $this)();

        return ltrim(ob_get_clean());
    }

    public function makeFromString($content, $vars = []): string
    {
        throw new \Exception('Views from strings are not supported with the native Yoyo view provider.');
    }

    public function exists($template)
    {
        foreach ($this->viewPath as $path) {
            if (file_exists("{$path}/{$template}.php")) {
                $this->templatePathsCache[$template] = "{$path}/{$template}.php";

                return true;
            }
        }

        $this->templatePathsCache[$template] = false;

        return false;
    }
}
