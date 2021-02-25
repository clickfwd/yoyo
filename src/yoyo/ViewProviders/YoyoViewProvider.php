<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;
use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
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

    public function startYoyoRendering($component): void
    {
        $this->view->startYoyoRendering($component);
    }

    public function stopYoyoRendering(): void
    {
        //
    }

    public function render($name, $vars = []): ViewProviderInterface
    {
        $this->name = $name;

        $this->vars = $vars;

        return $this;
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
        return $this->view->addNamespace($namespace, $hints);
    }

    public function prependNamespace($namespace, $hints)
    {
        return $this->view->prependNamespace($namespace, $hints);
    }

    public function addLocation($location)
    {
        return $this->view->addLocation($location);
    }

    public function prependLocation($location)
    {
        return $this->view->prependLocation($location);
    }

    public function __toString()
    {
        return $this->view->render($this->name, $this->vars);
    }
}
