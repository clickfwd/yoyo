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
        return $this->view->render($this->name, $this->vars);
    }
}
