<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;
use InvalidArgumentException;

class View
{
    protected $paths;

    protected $views;

    protected $yoyoComponent;

    protected static $hints;

    public function __construct($paths)
    {
        $paths = (array) $paths;

        $this->paths = array_map([$this, 'resolvePath'], $paths);
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

    public function render($name, $vars = []): string
    {
        $path = $this->exists($name);

        ob_start();
        
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

    public function exists($name)
    {
        if (isset($this->views[$name])) {
            return $this->views[$name];
        }

        if ($this->hasHintInformation($name = trim($name))) {
            return $this->views[$name] = $this->findNamespacedView($name);
        }

        return $this->views[$name] = $this->findInPaths($name, $this->paths);
    }

    public function addLocation($location)
    {
        $this->paths[] = $this->resolvePath($location);
    }

    public function prependLocation($location)
    {
        array_unshift($this->paths, $this->resolvePath($location));
    }

    protected function findInPaths($name, $paths)
    {
        $templatePath = str_replace('.', '/', $name);

        foreach ($paths as $path) {
            if (file_exists($location = "{$path}/{$templatePath}.php")) {
                return $location;
            }
        }

        throw new InvalidArgumentException("View [{$name}] not found.");
    }

    protected function findNamespacedView($name)
    {
        [$namespace, $view] = $this->parseNamespaceSegments($name);

        return $this->findInPaths($view, static::$hints[$namespace]);
    }

    protected function parseNamespaceSegments($name)
    {
        $segments = explode(ViewProviderInterface::HINT_PATH_DELIMITER, $name);

        if (count($segments) !== 2) {
            throw new InvalidArgumentException("View [{$name}] has an invalid name.");
        }

        if (! isset(static::$hints[$segments[0]])) {
            throw new InvalidArgumentException("No hint path defined for [{$segments[0]}].");
        }

        return $segments;
    }

    public function addNamespace($namespace, $hints)
    {
        $hints = (array) $hints;

        if (isset(static::$hints[$namespace])) {
            $hints = array_merge(static::$hints[$namespace], $hints);
        }

        static::$hints[$namespace] = $hints;
    }

    public function prependNamespace($namespace, $hints)
    {
        $hints = (array) $hints;

        if (isset(static::$hints[$namespace])) {
            $hints = array_merge($hints, static::$hints[$namespace]);
        }

        static::$hints[$namespace] = $hints;
    }

    public function hasHintInformation($name)
    {
        return strpos($name, ViewProviderInterface::HINT_PATH_DELIMITER) > 0;
    }

    protected function resolvePath($path)
    {
        return realpath($path) ?: $path;
    }
}
