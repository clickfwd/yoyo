<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;
use InvalidArgumentException;

class YoyoViewProvider extends BaseViewProvider implements ViewProviderInterface
{
    protected $view;

    protected $name;

    protected $vars;

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function render($name, $vars = []): ViewProviderInterface
    {
        $pending = $this->pendingRender();
        $pending->name = $name;
        $pending->vars = $vars;

        return $pending;
    }

    public function makeFromString($content, $vars = []): string
    {
        return $this->view->makeFromString($content, $vars);
    }

    public function exists($name): bool
    {
        try {
            return $this->view->exists($name);
        } catch (InvalidArgumentException $e) {
            throw new ComponentNotFound($name);
        }
    }

    public function addNamespace($namespace, $hints)
    {
        $this->view->addNamespace($namespace, $hints);

        return $this;
    }

    public function prependNamespace($namespace, $hints)
    {
        $this->view->prependNamespace($namespace, $hints);

        return $this;
    }

    public function addLocation($location)
    {
        $this->view->addLocation($location);

        return $this;
    }

    public function prependLocation($location)
    {
        $this->view->prependLocation($location);

        return $this;
    }

    public function __call(string $method, array $params)
    {
        return call_user_func_array([$this->view, $method], $params);
    }

    public function __toString()
    {
        $component = $this->yoyoComponent;

        if (! $this->hasYoyoComponent) {
            return $this->view->render($this->name, $this->vars);
        }

        $this->view->startYoyoRendering($component);

        try {
            return $this->view->render($this->name, $this->vars);
        } finally {
            $this->view->stopYoyoRendering();
        }
    }
}
