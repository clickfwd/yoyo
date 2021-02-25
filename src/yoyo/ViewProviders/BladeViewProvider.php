<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;

class BladeViewProvider extends BaseViewProvider implements ViewProviderInterface
{
    protected $view;

    protected $template;

    protected $vars;

    protected $engine;

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function startYoyoRendering($component): void
    {
        $this->engine = $this->view->getContainer()->get('view.engine.resolver')->resolve('blade');

        $this->engine->startYoyoRendering($component);
    }

    public function stopYoyoRendering(): void
    {
        $this->engine->stopYoyoRendering();
    }

    public function render($template, $vars = []): ViewProviderInterface
    {
        $this->template = $template;

        $this->vars = $vars;

        return $this;
    }

    public function makeFromString($content, $vars = []): string
    {
        $view = $this->view->make((new \Clickfwd\Yoyo\Blade\CreateBladeViewFromString)($this->view, $content));

        return $view->with($vars)->render();
    }

    public function exists($template): bool
    {
        return $this->view->exists($template);
    }

    public function getFinder()
    {
        return $this->view->getFinder();
    }

    public function addNamespace($namespace, $hints)
    {
        $this->getFinder()->addNamespace($namespace, $hints);
        return $this;
    }

    public function prependNamespace($namespace, $hints)
    {
        $this->getFinder()->prependNamespace($namespace, $hints);
        return $this;
    }

    public function addLocation($location)
    {
        $this->getFinder()->addLocation($location);
        return $this;
    }

    public function prependLocation($location)
    {
        $this->getFinder()->prependLocation($location);
        return $this;
    }

    public function __toString()
    {
        $output = (string) $this->view->make($this->template, $this->vars);

        $this->stopYoyoRendering();

        return $output;
    }
}
